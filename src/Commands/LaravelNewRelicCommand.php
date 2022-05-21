<?php

namespace JackWH\LaravelNewRelic\Commands;

use Illuminate\Console\Command;

class LaravelNewRelicCommand extends Command
{
    public $signature = 'laravel-new-relic';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
