<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Exchange;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);
        // Referrer-Policy forbids Referer, and Laravel excludes AJAX GETs from
        // previous URLs. Preserve the current Inertia page for validation redirects.
        if ($request->isMethod('GET') && $request->header('X-Inertia') && $response->getStatusCode() === 200) {
            $request->session()->setPreviousUrl('/'.ltrim($request->getRequestUri(), '/'));
        }

        return $response;
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'currencies' => Exchange::CURRENCIES,
            'hasAccess' => $request->session()->has('organizer_email') || count($request->session()->get('participants', [])) > 0,
            'organizerEmail' => $request->session()->get('organizer_email'),
            'notice' => fn () => $request->session()->get('notice'),
            //
        ];
    }
}
