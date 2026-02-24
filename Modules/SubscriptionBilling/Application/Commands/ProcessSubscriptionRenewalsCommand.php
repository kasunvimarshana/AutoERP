<?php

namespace Modules\SubscriptionBilling\Application\Commands;

use Illuminate\Console\Command;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Infrastructure\Jobs\RenewSubscriptionJob;

class ProcessSubscriptionRenewalsCommand extends Command
{
    protected $signature = 'subscriptions:process-renewals
                            {--tenant= : Limit processing to a specific tenant ID}
                            {--chunk=100 : Number of subscriptions to process per chunk}';

    protected $description = 'Dispatch renewal jobs for subscriptions due today in chunks to prevent execution timeout';

    public function __construct(private SubscriptionRepositoryInterface $subscriptionRepo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId  = (string) $this->option('tenant');
        $chunkSize = (int) $this->option('chunk');

        $dispatched = 0;

        $this->subscriptionRepo->chunkDueForRenewal(
            $tenantId,
            $chunkSize,
            function ($subscriptions) use (&$dispatched) {
                foreach ($subscriptions as $subscription) {
                    RenewSubscriptionJob::dispatch($subscription->id);
                    $dispatched++;
                }
            }
        );

        $this->info("Dispatched {$dispatched} renewal job(s) to the queue.");

        return self::SUCCESS;
    }
}
