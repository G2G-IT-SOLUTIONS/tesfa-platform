<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = explode(',', config('app.available_locales', 'en'));
        $locale = $request->user()?->preferred_locale
            ?? $request->session()->get('locale')
            ?? config('app.locale', 'en');

        if (in_array($locale, $available, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
