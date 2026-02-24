<?php

namespace Modules\SubscriptionBilling\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionCreated;

class CreateSubscriptionUseCase
{
    public function __construct(
        private SubscriptionPlanRepositoryInterface $planRepo,
        private SubscriptionRepositoryInterface     $subscriptionRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? null;

            $plan = $this->planRepo->findById($data['plan_id']);

            if (! $plan) {
                throw new ModelNotFoundException("Subscription plan [{$data['plan_id']}] not found.");
            }

            if (! $plan->is_active) {
                throw new DomainException("Subscription plan [{$data['plan_id']}] is not active.");
            }

            $now         = now();
            $periodStart = $now->copy();
            $periodEnd   = $this->calculatePeriodEnd($now->copy(), $plan->billing_cycle);

            $trialEndsAt = null;
            $status      = 'active';

            if ((int) $plan->trial_days > 0) {
                $trialEndsAt = $now->copy()->addDays((int) $plan->trial_days)->toDateTimeString();
                $status      = 'trial';
            }

            $subscription = $this->subscriptionRepo->create([
                'tenant_id'            => $tenantId,
                'plan_id'              => $plan->id,
                'subscriber_type'      => $data['subscriber_type'],
                'subscriber_id'        => $data['subscriber_id'],
                'status'               => $status,
                'amount'               => (string) $plan->price,
                'current_period_start' => $periodStart->toDateTimeString(),
                'current_period_end'   => $periodEnd->toDateTimeString(),
                'trial_ends_at'        => $trialEndsAt,
                'cancelled_at'         => null,
            ]);

            Event::dispatch(new SubscriptionCreated($subscription->id));

            return $subscription;
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
