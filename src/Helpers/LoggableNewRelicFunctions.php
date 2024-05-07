<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Loggable New Relic Functions
|--------------------------------------------------------------------------
|
| These functions can be loaded by the NewRelicServiceProvider in
| environments marked as 'loggable' in the config file, to simulate
| actions taken by the package.
|
*/

use JackWH\LaravelNewRelic\NewRelicTransaction;

if (! function_exists('newrelic_name_transaction')) {
    /**
     * Name a transaction for New Relic.
     */
    function newrelic_name_transaction(string $name): void
    {
        app('log')->debug(
            'New Relic: transaction is now named "'
            . app(NewRelicTransaction::class)->identifier(false)
            . '", with object ID ' . spl_object_id(app(NewRelicTransaction::class))
        );
    }
}

if (! function_exists('newrelic_add_custom_parameter')) {
    /**
     * Add a custom parameter for New Relic.
     */
    function newrelic_add_custom_parameter(string $key, int|float|string $value): void
    {
        app('log')->debug(
            'New Relic: '
            . app(NewRelicTransaction::class)->identifier()
            . ' set custom parameter "' . $key . '" to "' . $value . '"',
        );
    }
}

if (! function_exists('newrelic_background_job')) {
    /**
     * Tell New Relic this transaction is a background job.
     */
    function newrelic_background_job(): void
    {
        app('log')->debug(
            'New Relic: '
            . app(NewRelicTransaction::class)->identifier()
            . ' is a background job.'
        );
    }
}

if (! function_exists('newrelic_ignore_transaction')) {
    /**
     * Tell New Relic to ignore the current transaction.
     */
    function newrelic_ignore_transaction(): void
    {
        app('log')->debug(
            'New Relic: '
            . app(NewRelicTransaction::class)->identifier()
            . ' ignored.'
        );
    }
}

if (! function_exists('newrelic_start_transaction')) {
    /**
     * Tell New Relic to start the current transaction.
     */
    function newrelic_start_transaction(string $appName): void
    {
        app('log')->debug(
            'New Relic: '
            . app(NewRelicTransaction::class)->identifier()
            . ' started for application "' . $appName . '"'
        );
    }
}

if (! function_exists('newrelic_end_transaction')) {
    /**
     * Tell New Relic to end the current transaction.
     */
    function newrelic_end_transaction(): void
    {
        app('log')->debug(
            'New Relic: ' .
            app(NewRelicTransaction::class)->identifier()
            . ' ended.'
        );
    }
}
