<?php

namespace App\Console\Commands;

use App\Outbox\OutboxProcessor;
use Illuminate\Console\Command;

/**
 * Polls the outbox_messages table and publishes pending events to the message
 * broker and webhook subscribers.
 *
 * Run continuously in production:
 *   php artisan outbox:process --sleep=5
 *
 * Or schedule it in routes/console.php:
 *   Schedule::command('outbox:process')->everyMinute();
 */
class ProcessOutboxCommand extends Command
{
    protected $signature = 'outbox:process
                            {--batch=50 : Maximum messages to process per iteration}
                            {--sleep=5  : Seconds to wait between iterations when idle}
                            {--once     : Process a single batch then exit}
                            {--retry    : Reset failed messages to pending before processing}';

    protected $description = 'Process pending outbox messages and publish them to the message broker';

    public function __construct(private readonly OutboxProcessor $processor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $sleep     = (int) $this->option('sleep');
        $once      = (bool) $this->option('once');
        $retry     = (bool) $this->option('retry');

        if ($retry) {
            $reset = $this->processor->retryFailed();
            $this->info("Reset {$reset} failed message(s) to pending.");
        }

        do {
            $published = $this->processor->process($batchSize);

            if ($published > 0) {
                $this->info("[" . now()->toTimeString() . "] Published {$published} outbox message(s).");
            }

            if (!$once && $published === 0) {
                sleep($sleep);
            }
        } while (!$once);

        return self::SUCCESS;
    }
}
