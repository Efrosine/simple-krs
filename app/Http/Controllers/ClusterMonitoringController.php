<?php

namespace App\\Http\\Controllers;

use Illuminate\\Http\\Client\\ConnectionException;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Http;
use Illuminate\\Support\\Facades\\Log;
use Throwable;

class ClusterMonitoringController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $nodes = collect(config('node.peers', []))
            ->map(fn (array $peer): array => $this->checkNode($peer))
            ->values();

        return response()->json([
            'checked_at' => now()->toIso8601String(),
            'nodes' => $nodes,
            'databases' => [
                'primary' => $this->checkPrimary(),
                'replica' => $this->checkReplica(),
            ],
        ]);
    }

    private function checkNode(array $peer): array
    {
        $nodeId = $peer['id'] ?? 'unknown-node';
        $url = rtrim((string) ($peer['url'] ?? ''), '/') . '/api/node';
        $startedAt = microtime(true);

        try {
            // Separate connection timeout from total request timeout so network
            // setup failures can be distinguished from a slow health endpoint.
            $response = Http::connectTimeout(1)
                ->timeout(3)
                ->acceptJson()
                ->get($url);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $payload = $response->json();

            if (! $response->successful()) {
                $error = 'HTTP ' . $response->status();
                $this->logNodeProbeFailure($nodeId, $url, $error, $durationMs, [
                    'http_status' => $response->status(),
                ]);

                return [
                    'id' => $nodeId,
                    'status' => 'unhealthy',
                    'hostname' => $payload['hostname'] ?? null,
                    'reported_node_id' => $payload['node_id'] ?? null,
                    'error' => $error,
                    'duration_ms' => $durationMs,
                    'checked_at' => now()->toIso8601String(),
                ];
            }

            if (! is_array($payload) || ($payload['status'] ?? null) !== 'ok') {
                $error = 'Invalid health-check payload';
                $this->logNodeProbeFailure($nodeId, $url, $error, $durationMs, [
                    'http_status' => $response->status(),
                ]);

                return [
                    'id' => $nodeId,
                    'status' => 'unhealthy',
                    'hostname' => is_array($payload) ? ($payload['hostname'] ?? null) : null,
                    'reported_node_id' => is_array($payload) ? ($payload['node_id'] ?? null) : null,
                    'error' => $error,
                    'duration_ms' => $durationMs,
                    'checked_at' => now()->toIso8601String(),
                ];
            }

            return [
                'id' => $nodeId,
                'status' => 'healthy',
                'hostname' => $payload['hostname'] ?? null,
                'reported_node_id' => $payload['node_id'] ?? null,
                'duration_ms' => $durationMs,
                'checked_at' => $payload['checked_at'] ?? now()->toIso8601String(),
            ];
        } catch (ConnectionException $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $error = 'Connection or timeout error';
            $this->logNodeProbeFailure($nodeId, $url, $error, $durationMs, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return [
                'id' => $nodeId,
                'status' => 'unhealthy',
                'hostname' => null,
                'error' => $error,
                'duration_ms' => $durationMs,
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $error = 'Health-check error';
            $this->logNodeProbeFailure($nodeId, $url, $error, $durationMs, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return [
                'id' => $nodeId,
                'status' => 'unhealthy',
                'hostname' => null,
                'error' => $error,
                'duration_ms' => $durationMs,
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }

    private function logNodeProbeFailure(
        string $nodeId,
        string $url,
        string $reason,
        int $durationMs,
        array $context = [],
    ): void {
        Log::warning('Cluster node health probe failed', array_merge([
            'node' => $nodeId,
            'url' => $url,
            'reason' => $reason,
            'duration_ms' => $durationMs,
        ], $context));
    }

    private function checkPrimary(): array
    {
        try {
            $connection = DB::connection('pgsql');
            $isReplica = filter_var($connection->selectOne('SELECT pg_is_in_recovery() AS value')->value, FILTER_VALIDATE_BOOLEAN);

            return [
                'status' => $isReplica ? 'degraded' : 'healthy',
                'role' => $isReplica ? 'standby (unexpected)' : 'primary',
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $exception) {
            Log::warning('Cluster primary database health check failed', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return ['status' => 'unhealthy', 'role' => 'primary', 'error' => 'Connection failed', 'checked_at' => now()->toIso8601String()];
        }
    }

    private function checkReplica(): array
    {
        try {
            $connection = DB::connection('pgsql_replica');
            $isReplica = filter_var($connection->selectOne('SELECT pg_is_in_recovery() AS value')->value, FILTER_VALIDATE_BOOLEAN);
            $lag = $connection->selectOne(
                'SELECT CASE WHEN pg_last_wal_receive_lsn() IS NULL OR pg_last_wal_replay_lsn() IS NULL THEN NULL ELSE pg_wal_lsn_diff(pg_last_wal_receive_lsn(), pg_last_wal_replay_lsn()) END AS lag_bytes'
            );

            return [
                'status' => $isReplica ? 'healthy' : 'degraded',
                'role' => $isReplica ? 'replica' : 'primary (unexpected)',
                'lag_bytes' => $lag?->lag_bytes === null ? null : (int) $lag->lag_bytes,
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $exception) {
            Log::warning('Cluster replica database health check failed', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return ['status' => 'unhealthy', 'role' => 'replica', 'error' => 'Connection failed', 'checked_at' => now()->toIso8601String()];
        }
    }
}
