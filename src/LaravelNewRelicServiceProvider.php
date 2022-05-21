<?php

namespace JackWH\LaravelNewRelic;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use JackWH\LaravelNewRelic\Commands\NewRelicDeployCommand;
use JackWH\LaravelNewRelic\Middleware\NewRelicMiddleware;

class LaravelNewRelicServiceProvider extends ServiceProvider
{
    /**
     * Register the New Relic Service Provider.
     */
    public function register(): void
    {
        // Load in the package and user configurations
        $this->mergeConfigFrom(
            __DIR__ . '/../config/new-relic.php',
            'new-relic'
        );

        // Bind the transaction and handler classes to the container.
        // We bind them as scoped singletons, meaning they will be
        // automatically reset at the end of each lifecycle request.
        $this->app->scoped(NewRelicTransaction::class, fn($app) => new NewRelicTransaction());
        $this->app->scoped(NewRelicTransactionHandler::class, fn($app) => new NewRelicTransactionHandler());
    }

    /**
     * Boot the New Relic Service Provider.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/new-relic.php' => config_path('new-relic.php'),
        ]);

        if ($this->app->runningInConsole()) {
            // Register the new-relic:deploy command in the console
            $this->commands([NewRelicDeployCommand::class]);
        }

        if (app(NewRelicTransactionHandler::class)::newRelicEnabled()) {
            app(NewRelicTransactionHandler::class)->configureNewRelic();
        }
    }
}
