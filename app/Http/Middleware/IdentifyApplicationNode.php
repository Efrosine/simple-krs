<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IdentifyApplicationNode
{
    public function handle(Request $request, Closure $next): Response
    {
        $nodeId = (string) config('node.id', 'local-node');

        Log::withContext([
            'app_node' => $nodeId,
        ]);

        $response = $next($request);

        $response->headers->set('X-App-Node', $nodeId);

        Log::info('HTTP request served', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
