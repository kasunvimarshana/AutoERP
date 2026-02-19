<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Modules\Billing\Enums\UsageType;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Models\SubscriptionUsage;
use Modules\Billing\Repositories\SubscriptionRepository;
use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;

/**
 * Usage Tracking Service
 *
 * Tracks and records usage-based billing metrics.
 */
class UsageTrackingService
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private BillingCalculationService $calculationService
    ) {}

    /**
     * Record usage for a subscription.
     */
    public function recordUsage(
        int $subscriptionId,
        UsageType $type,
        string $quantity,
        string $unitPrice = '0.00',
        ?string $description = null
    ): SubscriptionUsage {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);

        return TransactionHelper::execute(function () use ($subscription, $type, $quantity, $unitPrice, $description) {
            $amount = $this->calculationService->calculateUsageCharge($quantity, $unitPrice);

            return $subscription->usages()->create([
                'type' => $type,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'recorded_at' => now(),
            ]);
        });
    }

    /**
     * Get total usage for a subscription in current period.
     */
    public function getCurrentPeriodUsage(int $subscriptionId, ?UsageType $type = null): array
    {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);

        $query = $subscription->usages()
            ->whereBetween('recorded_at', [
                $subscription->current_period_start,
                $subscription->current_period_end,
            ]);

        if ($type) {
            $query->where('type', $type);
        }

        $usages = $query->get();

        $totalQuantity = '0.00';
        $totalAmount = '0.00';

        foreach ($usages as $usage) {
            $totalQuantity = MathHelper::add($totalQuantity, $usage->quantity);
            $totalAmount = MathHelper::add($totalAmount, $usage->amount);
        }

        return [
            'count' => $usages->count(),
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
            'usages' => $usages,
        ];
    }

    /**
     * Check if usage limit is exceeded.
     */
    public function checkUsageLimit(int $subscriptionId, UsageType $type, string $limit): bool
    {
        $currentUsage = $this->getCurrentPeriodUsage($subscriptionId, $type);

        return MathHelper::greaterThan($currentUsage['total_quantity'], $limit);
    }
}
