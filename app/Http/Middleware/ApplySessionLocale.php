<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ApplySessionLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $language = $request->session()->get('settings.default_language');
        $locale = $language === 'en_US' ? 'en' : ($language === 'id_ID' ? 'id' : config('app.locale', 'id'));

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
