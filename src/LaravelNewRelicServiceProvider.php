<?php

declare(strict_types=1);

namespace JackWH\LaravelNewRelic;

use Illuminate\Support\ServiceProvider;
use JackWH\LaravelNewRelic\Commands\NewRelicDeployCommand;

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
        $this->app->scoped(NewRelicTransaction::class, static fn ($app): NewRelicTransaction => new NewRelicTransaction());
        $this->app->scoped(NewRelicTransactionHandler::class, static fn ($app): NewRelicTransactionHandler => new NewRelicTransactionHandler());
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
