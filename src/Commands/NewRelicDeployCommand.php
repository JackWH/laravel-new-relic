<?php

declare(strict_types=1);

namespace JackWH\LaravelNewRelic\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class NewRelicDeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    public $signature = 'new-relic:deploy
                        {description? : A description of the change}
                        {revision? : The git revision hash}';

    /**
     * The console command description.
     */
    public $description = 'Notify New Relic of a new deployment.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $revision = $this->argument('revision') ?: $this->detectRevision();

        $nr = Http::withHeaders(['Api-Key' => config('new-relic.deployments.api_key')])
            ->asJson()
            ->post(config('new-relic.deployments.endpoint') . 'applications/' . config('new-relic.deployments.app_id') . '/deployments.json', [
                'deployment' => [
                    'revision' => $revision,
                    'description' => $this->argument('description'),
                    'user' => config('new-relic.deployments.user'),
                ],
            ]);

        if ($nr->successful()) {
            $this->info('Notified New Relic!');

            return parent::SUCCESS;
        }

        $this->warn('Could not notify New Relic [HTTP ' . $nr->status() . ']');
        $this->warn('See: https://rpm.eu.newrelic.com/api/explore/application_deployments/create');

        return parent::FAILURE;
    }

    /**
     * Attempt to auto-detect the current git revision hash.
     */
    public function detectRevision(): ?string
    {
        if (! config('new-relic.deployments.detect_hash')) {
            return null;
        }

        try {
            return trim(exec('git log --pretty="%H" -n1 HEAD'));
        } catch (Throwable $throwable) {
            $this->warn('Could not auto-detect revision hash: ' . $throwable->getMessage());
        }

        return null;
    }
}
