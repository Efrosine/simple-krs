<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ClusterMonitoringController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $nodes = collect(config('node.peers', []))->map(function (array $peer): array {
            try {
                $response = Http::timeout(1.5)->get(rtrim($peer['url'], '/') . '/api/node');

                if (! $response->successful()) {
                    return ['id' => $peer['id'], 'status' => 'unhealthy', 'hostname' => null, 'checked_at' => now()->toIso8601String()];
                }

                $payload = $response->json();

                return [
                    'id' => $peer['id'],
                    'status' => ($payload['status'] ?? null) === 'ok' ? 'healthy' : 'unhealthy',
                    'hostname' => $payload['hostname'] ?? null,
                    'reported_node_id' => $payload['node_id'] ?? null,
                    'checked_at' => $payload['checked_at'] ?? now()->toIso8601String(),
                ];
            } catch (Throwable) {
                return ['id' => $peer['id'], 'status' => 'unhealthy', 'hostname' => null, 'checked_at' => now()->toIso8601String()];
            }
        })->values();

        return response()->json([
            'checked_at' => now()->toIso8601String(),
            'nodes' => $nodes,
            'databases' => [
                'primary' => $this->checkPrimary(),
                'replica' => $this->checkReplica(),
            ],
        ]);
    }

    private function checkPrimary(): array
    {
        try {
            $connection = DB::connection('pgsql');
            $isReplica = (bool) $connection->selectOne('SELECT pg_is_in_recovery() AS value')->value;

            return [
                'status' => $isReplica ? 'degraded' : 'healthy',
                'role' => $isReplica ? 'standby (unexpected)' : 'primary',
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $exception) {
            return ['status' => 'unhealthy', 'role' => 'primary', 'error' => 'Connection failed', 'checked_at' => now()->toIso8601String()];
        }
    }

    private function checkReplica(): array
    {
        try {
            $connection = DB::connection('pgsql_replica');
            $isReplica = (bool) $connection->selectOne('SELECT pg_is_in_recovery() AS value')->value;
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
            return ['status' => 'unhealthy', 'role' => 'replica', 'error' => 'Connection failed', 'checked_at' => now()->toIso8601String()];
        }
    }
}
