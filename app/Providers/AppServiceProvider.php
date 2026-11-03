<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('create-exchange', fn (Request $request) => Limit::perHour(5)->by('create:'.$request->ip()));
        RateLimiter::for('access-link', fn (Request $request) => Limit::perMinute(60)->by('access:'.$request->ip()));
        RateLimiter::for('join-exchange', fn (Request $request) => Limit::perMinute(60)->by('join:'.$request->ip()));
        RateLimiter::for('draw-exchange', fn (Request $request) => Limit::perMinute(5)->by('draw:'.$request->ip()));
        if ($this->app->environment('production')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME));
        }
    }
}
