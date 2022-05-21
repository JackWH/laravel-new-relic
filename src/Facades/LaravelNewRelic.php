<?php

namespace JackWH\LaravelNewRelic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JackWH\LaravelNewRelic\LaravelNewRelic
 */
class LaravelNewRelic extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-new-relic';
    }
}
