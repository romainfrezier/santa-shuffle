<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public const SUPPORTED = ['en', 'fr', 'es', 'de'];

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(self::resolve($request));

        return $next($request);
    }

    public static function resolve(Request $request): string
    {
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;
        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = $request->hasHeader('Accept-Language')
                ? $request->getPreferredLanguage(self::SUPPORTED)
                : config('app.locale', 'en');
        }

        return in_array($locale, self::SUPPORTED, true) ? $locale : 'en';
    }
}
