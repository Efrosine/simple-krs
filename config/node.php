<?php

$peers = array_filter(array_map('trim', explode(',', (string) env(
    'APP_CLUSTER_NODES',
    'app-1=http://app-1:8000,app-2=http://app-2:8000,app-3=http://app-3:8000'
))));

return [
    /*
    |--------------------------------------------------------------------------
    | Application Node Identity
    |--------------------------------------------------------------------------
    |
    | Set APP_NODE_ID to a stable, human-readable identifier for each app
    | instance. Container hostname is a useful fallback for local demos.
    |
    */
    'id' => env('APP_NODE_ID', env('HOSTNAME', gethostname() ?: 'local-node')),

    /*
    |--------------------------------------------------------------------------
    | Cluster peers
    |--------------------------------------------------------------------------
    |
    | Use service DNS names, never container IP addresses. Configure the list
    | through APP_CLUSTER_NODES for a different Compose topology.
    |
    */
    'peers' => array_values(array_map(static function (string $peer): array {
        [$id, $url] = array_pad(explode('=', $peer, 2), 2, '');

        return ['id' => trim($id), 'url' => trim($url)];
    }, array_filter($peers, static fn (string $peer): bool => str_contains($peer, '=') && str_contains($peer, '://')))),
];
