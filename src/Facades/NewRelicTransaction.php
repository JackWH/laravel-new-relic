<?php

namespace JackWH\LaravelNewRelic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JackWH\LaravelNewRelic\NewRelicTransaction
 */
class NewRelicTransaction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'NewRelicTransaction';
    }
}
