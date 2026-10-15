<?php

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
];
