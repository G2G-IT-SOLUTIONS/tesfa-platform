<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPhone
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->phone_verified_at) {
            return redirect()->route('verify.otp');
        }

        return $next($request);
    }
}
