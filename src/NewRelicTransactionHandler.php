<?php

namespace JackWH\LaravelNewRelic;

use \Illuminate\Console;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * This class handles an incoming request to the application,
 * and configures a transaction for it to send to New Relic.
 */
class NewRelicTransactionHandler
{
    /**
     * Check if the New Relic extension is installed, and enabled for the current environment.
     */
    public static function newRelicEnabled(): bool
    {
        // If we're running in a loggable environment, boot up the helper functions.
        if (app()->environment(config('new-relic.loggable'))) {
            require_once (__DIR__ . '/Helpers/LoggableNewRelicFunctions.php');
            return true;
        }

        return extension_loaded('newrelic') && app()->environment(config('new-relic.environments'));
    }

    /**
     * Configure New Relic to handle different types of Laravel requests and actions.
     */
    public function configureNewRelic(): void
    {
        // Set up New Relic within a try/catch, so that if there's any misconfiguration,
        // we won't risk unexpectedly taking down a production server.
        try {
            $this->httpRequests();
            $this->cliRequests();
            $this->queueHandling();
            $this->artisanCommands();
            $this->scheduledTasks();
        } catch (\Throwable $throwable) {
            if (app()->environment(config('new-relic.loggable'))) {
                throw $throwable;
            }
            Log::error('Error configuring New Relic: ' . $throwable->getMessage(), $throwable->getTrace());
        }
    }

    /**
     * Configure New Relic for CLI requests to the application.
     */
    public function cliRequests(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        // Apply our own early determination of the transaction name,
        // and tell New Relic this is a background job.
        app(NewRelicTransaction::class)
            ->setName(Str::before($this->getCommandString(), ' '))
            ->addParameter('command', collect($this->getCommandArgs())->implode(' '))
            ->background();
    }

    /**
     * Configure New Relic for HTTP requests to the application.
     */
    public function httpRequests(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        // Register the NewRelicMiddleware for HTTP requests
        if ($middleware = config('new-relic.http.middleware')) {
            app(\Illuminate\Contracts\Http\Kernel::class)->pushMiddleware( $middleware);
        }
    }

    /**
     * Configure New Relic for Queue handling.
     */
    public function queueHandling(): void
    {
        /**
         * Before each job begins processing, start a new transaction.
         */
        app('queue')->before(function(\Illuminate\Queue\Events\JobProcessing $event) {
            if ($this->shouldIgnoreJob($event->connectionName, $event->job->getQueue(), $event->job)) {
                app(NewRelicTransaction::class)->ignore();
                return;
            }

            // Start a new transaction for this job
            app(NewRelicTransaction::class)
                ->start(
                    config('new-relic.queue.prefix') .
                    (method_exists($event->job, 'resolveName'))
                        ? $event->job->resolveName()
                        : $event->job->getName()
                )->addParameter('queue', $event->job->getQueue())
                ->addParameter('connection', $event->connectionName);
        });

        /**
         * After each job finishes processing, end the previous transaction.
         */
        app('queue')->after(function(\Illuminate\Queue\Events\JobProcessed $event) {
            app(NewRelicTransaction::class)->end();
        });
    }

    /**
     * Determine whether a queue connection, queue name, or job should be ignored.
     */
    public function shouldIgnoreJob(
        ?string $connection = null,
        ?string $queue = null,
        ?\Illuminate\Contracts\Queue\Job $job = null,
    ): bool {
        if ($connection !== null && Str::is(config('new-relic.queue.ignore.connections'), $connection)) {
            return true;
        }

        if ($queue !== null && Str::is(config('new-relic.queue.ignore.queues'), $queue)) {
            return true;
        }

        if ($job !== null && Str::is(config('new-relic.queue.ignore.jobs'), get_class($job))) {
            return true;
        }

        return false;
    }

    /**
     * Configure New Relic for Artisan commands made to the application.
     */
    public function artisanCommands(): void
    {
        /**
         * When an Artisan command starts executing, begin a New Relic transaction.
         */
        app('events')->listen(
            Console\Events\CommandStarting::class,
            function(Console\Events\CommandStarting $event) {
                if ($this->shouldIgnoreCommand($event->command)) {
                    app(NewRelicTransaction::class)->ignore();
                    return;
                }

                // End any previous transactions, as long as we're not still running in the same one,
                // then start a new transaction for this command.
                app(NewRelicTransaction::class)->start(
                    config('new-relic.artisan.prefix') . $event->command
                )->addParameter('command', collect($this->getCommandArgs())->implode(' '));
            });

        /**
         * When a command finishes executing, end the transaction with New Relic.
         */
        app('events')->listen(
            Console\Events\CommandFinished::class,
            function(Console\Events\CommandFinished $event) {
                app(NewRelicTransaction::class)->end();
            });
    }

    /**
     * Determine whether an Artisan command should be ignored.
     */
    public function shouldIgnoreCommand(?string $command = null): bool
    {
        return $command !== null && Str::is(config('new-relic.artisan.ignore'), $command);
    }

    /**
     * Configure New Relic for scheduled tasks made by the application.
     */
    public function scheduledTasks(): void
    {
        /**
         * When a scheduled task starts executing, begin a New Relic transaction.
         */
        app('events')->listen(
            Console\Events\ScheduledTaskStarting::class,
            function(\Illuminate\Console\Events\ScheduledTaskStarting $event) {
                if ($this->shouldIgnoreTask($event->task->description ?: $event->task->command)) {
                    app(NewRelicTransaction::class)->ignore();
                    return;
                }

                // End any previous transactions, as long as we're not still running in the same one,
                // then start a new transaction for this task.
                app(NewRelicTransaction::class)->start(
                    config('new-relic.scheduler.prefix') .
                        ($event->task->description ?: $this->parseTaskCommand($event->task->command))
                    )->addParameter(
                        'command',
                        collect($this->getCommandArgs())->implode(' ')
                    );
            });

        /**
         * When a scheduled task finishes, end the transaction with New Relic.
         */
        app('events')->listen(
            Console\Events\ScheduledTaskFinished::class,
            function(Console\Events\ScheduledTaskFinished $event) {
                app(NewRelicTransaction::class)->end();
            });
        app('events')->listen(
            Console\Events\ScheduledBackgroundTaskFinished::class,
            function(Console\Events\ScheduledBackgroundTaskFinished $event) {
                app(NewRelicTransaction::class)->end();
            });
    }

    /**
     * Determine whether a scheduled task should be ignored.
     */
    public function shouldIgnoreTask(?string $task = null): bool
    {
        return $task !== null && Str::is(config('new-relic.scheduler.ignore'), $task);
    }

    /**
     * Get any command arguments passed in to the current request.
     */
    protected function getCommandArgs(): array
    {
        return collect(request()->server())
            ->only('argv')
            ->flatten()
            ->toArray();
    }

    /**
     * Get any command arguments passed in to the current request, as a formatted string.
     */
    protected function getCommandString(): string
    {
        $cmdName = trim(collect($this->getCommandArgs())->map(
            fn(string $argument) => Str::contains($argument, '=')
                ? (Str::before($argument, '=') . '=?')
                : $argument
        )->implode(' '));

        return ltrim(
            Str::remove([base_path(), 'artisan '], $cmdName, false),
            '/ '
        );
    }

    /**
     * Parse a command string for a scheduled task, returning it in a simplified format.
     */
    protected function parseTaskCommand(string $taskCommand): string
    {
        $formattedCommand = Str::of($taskCommand)
            ->afterLast("'artisan' ")
            ->afterLast("artisan ")
            ->trim("'/ ");

        return $formattedCommand->contains(' ')
            ? $formattedCommand->before(' ')->toString()
            : $formattedCommand->toString();
    }
}
