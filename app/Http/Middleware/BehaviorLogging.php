<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BehaviorLogging
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('behavior_session_id')) {
            $request->session()->put('behavior_session_id', bin2hex(random_bytes(16)));
        }

        return $next($request);
    }
}
