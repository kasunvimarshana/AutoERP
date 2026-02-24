<?php

namespace Modules\SubscriptionBilling\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionRenewed;

class RenewSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(public readonly string $subscriptionId) {}

    public function handle(
        SubscriptionRepositoryInterface     $subscriptionRepo,
        SubscriptionPlanRepositoryInterface $planRepo,
    ): void {
        $subscription = $subscriptionRepo->findById($this->subscriptionId);

        if (! $subscription || ! in_array($subscription->status, ['active', 'trial'], true)) {
            return;
        }

        $plan = $planRepo->findById($subscription->plan_id);

        if (! $plan) {
            return;
        }

        $periodStart = now();
        $periodEnd   = $this->calculatePeriodEnd($periodStart->copy(), $plan->billing_cycle);

        $subscriptionRepo->update($this->subscriptionId, [
            'status'               => 'active',
            'current_period_start' => $periodStart->toDateTimeString(),
            'current_period_end'   => $periodEnd->toDateTimeString(),
            'trial_ends_at'        => null,
        ]);

        Event::dispatch(new SubscriptionRenewed($this->subscriptionId));
    }

    private function calculatePeriodEnd(\Carbon\Carbon $from, string $billingCycle): \Carbon\Carbon
    {
        return match ($billingCycle) {
            'monthly'   => $from->addMonth(),
            'quarterly' => $from->addMonths(3),
            'annually'  => $from->addYear(),
            default     => $from->addMonth(),
        };
    }
}
