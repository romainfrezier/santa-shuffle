<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Exchange;
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
        RateLimiter::for('login-send', fn (Request $request) => [Limit::perHour(10)->by('login-ip:'.$request->ip()), Limit::perHour(5)->by('login-email:'.hash('sha256', strtolower(is_string($request->input('email')) ? trim($request->input('email')) : '')))]);
        RateLimiter::for('login-verify', fn (Request $request) => Limit::perMinute(20)->by('login-verify:'.$request->ip()));
        RateLimiter::for('manage-event', fn (Request $request) => [Limit::perMinute(30)->by('manage-ip:'.$request->ip()), Limit::perHour(200)->by('manage-event:'.($request->route('exchange') instanceof Exchange ? $request->route('exchange')->uuid : $request->route('exchange')).':'.$request->session()->getId())]);
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
