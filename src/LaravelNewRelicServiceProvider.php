<?php

namespace JackWH\LaravelNewRelic;

use JackWH\LaravelNewRelic\Commands\LaravelNewRelicCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelNewRelicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-new-relic')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(LaravelNewRelicCommand::class);
    }
}
