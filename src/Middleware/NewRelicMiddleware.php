<?php

namespace JackWH\LaravelNewRelic\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use JackWH\LaravelNewRelic\LaravelNewRelicServiceProvider;
use JackWH\LaravelNewRelic\NewRelicTransaction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JackWH\LaravelNewRelic\NewRelicTransactionHandler;

class NewRelicMiddleware
{
    protected ?\Illuminate\Contracts\Auth\Authenticatable $user = null;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Ensure New Relic is enabled before continuing.
        if (! app(NewRelicTransactionHandler::class)::newRelicEnabled()) {
            return $next($request);
        }

        // Set the transaction name for New Relic, based on the HTTP request.
        app(NewRelicTransaction::class)
            ->setName(
                config('new-relic.http.prefix') . (
                    $this->getCustomTransactionName($request)
                    ?? $this->getLivewireTransactionName($request)
                    ?? $request->route()?->getName()
                    ?? $request->route()?->getActionName()
                    ?? $request->path()
                )
            )->addParameter(
                // Record the IP address, if configured.
                'ip_address',
                config('new-relic.http.visitors.record_ip_address') ? $request->ip() : null
            );

        // Tell the application to handle the incoming request before continuing...
        $response = $next($request);

        // Skip further New Relic configuration if required.
        if ((request()->is($this->ignoredRoutes()))
            || (request()->routeIs($this->ignoredRoutes()))
            || (request()->fullUrlIs($this->ignoredRoutes()))) {
            app(NewRelicTransaction::class)->ignore();

            return $response;
        }

        // With the response now prepared, we can access the authenticated user.
        if (config('new-relic.http.visitors.record_user_id')) {
            $this->user = Auth::user();
        }

        // Add custom parameters to the transaction.
        app(NewRelicTransaction::class)
            ->addParameter(
                'user_type',
                $this->user ? 'User' : config('new-relic.http.visitors.guest_label')
            )->addParameter(
                'user_id',
                $this->user?->getAuthIdentifier(),
            );

        // Return the previous response and continue.
        return $response;
    }

    /**
     * An array of routes where this middleware shouldn't be applied.
     */
    protected function ignoredRoutes(): array
    {
        return array_merge(config('new-relic.http.ignore'), [
            //
        ]);
    }

    /**
     * Rewrite any custom transaction names, by path => name.
     */
    protected function mapCustomTransactionNames(): array
    {
        return array_merge(config('new-relic.http.rewrite'), [
            //
        ]);
    }

    /**
     * Get a custom name for a transaction by the currently-requested URI.
     */
    protected function getCustomTransactionName(Request $request): ?string
    {
        return collect($this->mapCustomTransactionNames())
            ->mapWithKeys(fn(string $name, string $path) => [
                (Str::of($path)->trim('/')->toString() ?: '/') => $name,
            ])->get(
                Str::of($request->path())->trim('/')->toString() ?: '/'
            );
    }

    /**
     * If the current request is to Livewire's messaging endpoint, set a custom name from the component.
     */
    protected function getLivewireTransactionName(Request $request): ?string
    {
        if (! $request->routeIs('livewire.message')) {
            return null;
        }

        return 'livewire.' . $request->route()->parameter('name', 'message');
    }
}
