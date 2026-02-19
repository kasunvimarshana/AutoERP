<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Carbon\Carbon;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Exceptions\InvalidSubscriptionStatusException;
use Modules\Billing\Exceptions\PlanNotFoundException;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Repositories\PlanRepository;
use Modules\Billing\Repositories\SubscriptionRepository;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;

/**
 * Subscription Service
 *
 * Handles subscription lifecycle management including creation, renewal,
 * cancellation, and status transitions.
 */
class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private PlanRepository $planRepository,
        private BillingCalculationService $calculationService,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a new subscription.
     */
    public function createSubscription(array $data): Subscription
    {
        return TransactionHelper::execute(function () use ($data) {
            // Validate plan exists
            $plan = $this->planRepository->find($data['plan_id']);
            if (! $plan) {
                throw new PlanNotFoundException($data['plan_id']);
            }

            // Generate subscription code if not provided
            if (empty($data['subscription_code'])) {
                $data['subscription_code'] = $this->generateSubscriptionCode();
            }

            // Set default status
            $data['status'] = $data['status'] ?? ($plan->trial_days > 0 ? SubscriptionStatus::Trial : SubscriptionStatus::Active);

            // Calculate trial end date
            if ($plan->trial_days > 0 && empty($data['trial_ends_at'])) {
                $data['trial_ends_at'] = now()->addDays($plan->trial_days);
            }

            // Set start date
            $data['starts_at'] = $data['starts_at'] ?? now();

            // Calculate billing period
            $periodStart = isset($data['current_period_start']) ? Carbon::parse($data['current_period_start']) : now();
            $periodEnd = $this->calculatePeriodEnd($periodStart, $plan->interval, $plan->interval_count);

            $data['current_period_start'] = $periodStart;
            $data['current_period_end'] = $periodEnd;

            // Calculate amounts
            $calculations = $this->calculationService->calculateSubscriptionAmount(
                $plan->price,
                $data['discount_amount'] ?? '0.00',
                $data['tax_rate'] ?? config('billing.default_tax_rate', 0)
            );

            $data['amount'] = $calculations['amount'];
            $data['tax_amount'] = $calculations['tax_amount'];
            $data['total_amount'] = $calculations['total_amount'];

            return $this->subscriptionRepository->create($data);
        });
    }

    /**
     * Renew a subscription for the next billing period.
     */
    public function renewSubscription(int $subscriptionId): Subscription
    {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);

        if (! $subscription->status->isActive()) {
            throw new InvalidSubscriptionStatusException(
                $subscription->status->value,
                'renew'
            );
        }

        return TransactionHelper::execute(function () use ($subscription) {
            $plan = $subscription->plan;

            // Calculate new billing period
            $newPeriodStart = $subscription->current_period_end;
            $newPeriodEnd = $this->calculatePeriodEnd(
                $newPeriodStart,
                $plan->interval,
                $plan->interval_count
            );

            $subscription->update([
                'current_period_start' => $newPeriodStart,
                'current_period_end' => $newPeriodEnd,
                'status' => SubscriptionStatus::Active,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(int $subscriptionId, bool $immediately = false): Subscription
    {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);

        if (! $subscription->status->canBeCancelled()) {
            throw new InvalidSubscriptionStatusException(
                $subscription->status->value,
                'cancel'
            );
        }

        return TransactionHelper::execute(function () use ($subscription, $immediately) {
            $updateData = [
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ];

            if ($immediately) {
                $updateData['ends_at'] = now();
            } else {
                // Cancel at end of current billing period
                $updateData['ends_at'] = $subscription->current_period_end;
            }

            $subscription->update($updateData);

            return $subscription->fresh();
        });
    }

    /**
     * Suspend a subscription.
     */
    public function suspendSubscription(int $subscriptionId, ?string $reason = null): Subscription
    {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);

        if ($subscription->status === SubscriptionStatus::Cancelled) {
            throw new InvalidSubscriptionStatusException(
                $subscription->status->value,
                'suspend'
            );
        }

        return TransactionHelper::execute(function () use ($subscription, $reason) {
            $metadata = $subscription->metadata ?? [];
            $metadata['suspension_reason'] = $reason;

            $subscription->update([
                'status' => SubscriptionStatus::Suspended,
                'suspended_at' => now(),
                'metadata' => $metadata,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Reactivate a suspended subscription.
     */
    public function reactivateSubscription(int $subscriptionId): Subscription
    {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);

        if (! $subscription->status->canBeActivated()) {
            throw new InvalidSubscriptionStatusException(
                $subscription->status->value,
                'reactivate'
            );
        }

        return TransactionHelper::execute(function () use ($subscription) {
            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'suspended_at' => null,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(int $subscriptionId, int $newPlanId): Subscription
    {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);
        $newPlan = $this->planRepository->find($newPlanId);

        if (! $newPlan) {
            throw new PlanNotFoundException($newPlanId);
        }

        if (! $subscription->isActive()) {
            throw new InvalidSubscriptionStatusException(
                $subscription->status->value,
                'change plan'
            );
        }

        return TransactionHelper::execute(function () use ($subscription, $newPlan) {
            // Calculate prorated amount if needed
            // For simplicity, we'll apply the new plan from the next billing period

            $calculations = $this->calculationService->calculateSubscriptionAmount(
                $newPlan->price,
                $subscription->discount_amount,
                0 // Tax will be recalculated on payment
            );

            $subscription->update([
                'plan_id' => $newPlan->id,
                'amount' => $calculations['amount'],
                'total_amount' => $calculations['total_amount'],
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Generate unique subscription code.
     */
    private function generateSubscriptionCode(): string
    {
        $prefix = config('billing.subscription_code_prefix', 'SUB-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->subscriptionRepository->findByCode($code) !== null
        );
    }

    /**
     * Calculate period end date based on interval.
     */
    private function calculatePeriodEnd(Carbon $start, $interval, int $count): Carbon
    {
        return match ($interval->value) {
            'daily' => $start->copy()->addDays($count),
            'weekly' => $start->copy()->addWeeks($count),
            'monthly' => $start->copy()->addMonths($count),
            'quarterly' => $start->copy()->addMonths(3 * $count),
            'semi_annually' => $start->copy()->addMonths(6 * $count),
            'annually' => $start->copy()->addYears($count),
            default => $start->copy()->addMonth(),
        };
    }
}
