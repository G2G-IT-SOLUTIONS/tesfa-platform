<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::info('HTTP', [
            'method'   => $request->method(),
            'path'     => $request->path(),
            'status'   => $response->getStatusCode(),
            'duration' => "{$duration}ms",
        ]);

        return $response;
    }
}
