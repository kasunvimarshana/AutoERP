<?php

namespace Modules\SubscriptionBilling\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionRenewed;

class RenewSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface     $subscriptionRepo,
        private SubscriptionPlanRepositoryInterface $planRepo,
    ) {}

    public function execute(string $subscriptionId): object
    {
        return DB::transaction(function () use ($subscriptionId) {
            $subscription = $this->subscriptionRepo->findById($subscriptionId);

            if (! $subscription) {
                throw new ModelNotFoundException("Subscription [{$subscriptionId}] not found.");
            }

            if (! in_array($subscription->status, ['active', 'trial'], true)) {
                throw new DomainException("Only active or trial subscriptions can be renewed.");
            }

            $plan = $this->planRepo->findById($subscription->plan_id);

            if (! $plan) {
                throw new ModelNotFoundException("Subscription plan [{$subscription->plan_id}] not found.");
            }

            $periodStart = now();
            $periodEnd   = $this->calculatePeriodEnd($periodStart->copy(), $plan->billing_cycle);

            $updated = $this->subscriptionRepo->update($subscriptionId, [
                'status'               => 'active',
                'current_period_start' => $periodStart->toDateTimeString(),
                'current_period_end'   => $periodEnd->toDateTimeString(),
                'trial_ends_at'        => null,
            ]);

            Event::dispatch(new SubscriptionRenewed(
                subscriptionId:       $subscriptionId,
                tenantId:             (string) ($subscription->tenant_id ?? ''),
                subscriberId:         (string) ($subscription->subscriber_id ?? ''),
                planName:             (string) ($plan->name ?? ''),
                amount:               (string) ($subscription->amount ?? '0'),
                currency:             (string) ($plan->currency ?? 'USD'),
                currentPeriodStart:   $periodStart->toDateTimeString(),
                currentPeriodEnd:     $periodEnd->toDateTimeString(),
            ));

            return $updated;
        });
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
