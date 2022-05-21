<?php

namespace JackWH\LaravelNewRelic;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use JackWH\LaravelNewRelic\Commands\LaravelNewRelicCommand;

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
