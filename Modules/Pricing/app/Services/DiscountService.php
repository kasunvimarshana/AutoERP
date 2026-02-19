<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use App\Core\Services\BaseService;
use Modules\Pricing\Enums\DiscountType;
use Modules\Pricing\Repositories\DiscountRuleRepository;

/**
 * Discount Service
 *
 * Handles discount calculations
 */
class DiscountService extends BaseService
{
    public function __construct(DiscountRuleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Calculate discount for amount
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function calculateDiscount(string $amount, string $discountCode, array $context = []): ?array
    {
        $rule = $this->repository->findActiveByCode($discountCode);

        if (! $rule) {
            return null;
        }

        // Check minimum purchase amount
        if ($rule->min_purchase_amount && bccomp($amount, (string) $rule->min_purchase_amount, 2) < 0) {
            return null;
        }

        $discountAmount = match (DiscountType::from($rule->type)) {
            DiscountType::PERCENTAGE => $this->calculatePercentageDiscount($amount, (string) $rule->value),
            DiscountType::FIXED_AMOUNT => (string) $rule->value,
            default => '0.00',
        };

        // Apply max discount limit
        if ($rule->max_discount_amount && bccomp($discountAmount, (string) $rule->max_discount_amount, 2) > 0) {
            $discountAmount = (string) $rule->max_discount_amount;
        }

        // Ensure discount doesn't exceed amount
        if (bccomp($discountAmount, $amount, 2) > 0) {
            $discountAmount = $amount;
        }

        return [
            'discount_id' => $rule->id,
            'discount_code' => $rule->code,
            'discount_name' => $rule->name,
            'discount_type' => $rule->type,
            'discount_value' => (string) $rule->value,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Calculate cart-level discount
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function calculateCartDiscount(string $cartTotal, array $items, string $discountCode): array
    {
        $result = $this->calculateDiscount($cartTotal, $discountCode);

        if ($result) {
            // Increment usage count
            $rule = $this->repository->findByCode($discountCode);
            if ($rule) {
                $rule->incrementUsage();
            }
        }

        return $result ?? [
            'discount_amount' => '0.00',
        ];
    }

    /**
     * Calculate percentage discount
     */
    private function calculatePercentageDiscount(string $amount, string $percentage): string
    {
        return bcmul($amount, bcdiv($percentage, '100', 4), 2);
    }

    /**
     * Validate discount code
     */
    public function validateDiscountCode(string $code): bool
    {
        return $this->repository->findActiveByCode($code) !== null;
    }

    /**
     * Get active discount rules
     */
    public function getActiveRules(): mixed
    {
        return $this->repository->getActiveRulesOrderedByPriority();
    }
}
