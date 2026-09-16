<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGDPRConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->hasConsent('terms_of_service')) {
            if (!$request->is('legal/*') && !$request->is('logout')) {
                return redirect()->route('legal.consent');
            }
        }

        return $next($request);
    }
}
