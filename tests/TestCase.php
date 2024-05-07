<?php

declare(strict_types=1);

namespace JackWH\LaravelNewRelic\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use JackWH\LaravelNewRelic\LaravelNewRelicServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            static fn (string $modelName): string => 'JackWH\\LaravelNewRelic\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelNewRelicServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-new-relic_table.php.stub';
        $migration->up();
        */
    }
}
