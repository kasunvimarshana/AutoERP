<?php

namespace App\Services;

use App\Contracts\Pricing\PricingEngineInterface;
use App\Enums\PricingType;
use App\Models\PriceRule;
use App\Models\Product;

class PricingEngine implements PricingEngineInterface
{
    /**
     * Calculate price for a product in a given context.
     *
     * Context keys:
     *   - product_id (string)
     *   - variant_id (string|null)
     *   - tenant_id (string)
     *   - organization_id (string|null)
     *   - quantity (numeric string, default '1')
     *   - currency (string, default 'USD')
     *   - price_list_id (string|null) — optional override
     */
    public function calculate(array $context): string
    {
        $tenantId = $context['tenant_id'];
        $productId = $context['product_id'];
        $variantId = $context['variant_id'] ?? null;
        $quantity = (string) ($context['quantity'] ?? '1');
        $currency = $context['currency'] ?? 'USD';

        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);
        $basePrice = $product->base_price;

        $priceListId = $context['price_list_id'] ?? null;
        $rule = $this->resolveRule($tenantId, $productId, $variantId, $priceListId, $quantity, $currency);

        if ($rule === null) {
            return bcadd($basePrice, '0', 8);
        }

        return $this->applyRule($rule, $basePrice, $quantity);
    }

    private function resolveRule(
        string $tenantId,
        string $productId,
        ?string $variantId,
        ?string $priceListId,
        string $quantity,
        string $currency
    ): ?PriceRule {
        $query = PriceRule::query()
            ->whereHas('priceList', function ($q) use ($tenantId, $currency, $priceListId) {
                $q->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->where('currency', $currency)
                    ->where(function ($q2) {
                        $q2->whereNull('valid_from')->orWhere('valid_from', '<=', today());
                    })
                    ->where(function ($q2) {
                        $q2->whereNull('valid_until')->orWhere('valid_until', '>=', today());
                    });
                if ($priceListId) {
                    $q->where('id', $priceListId);
                }
            })
            ->where('is_active', true)
            ->where(function ($q) use ($productId, $variantId) {
                if ($variantId) {
                    // Match variant-specific rule OR product-level rule (no variant restriction)
                    $q->where('variant_id', $variantId)
                        ->orWhere(function ($q2) use ($productId) {
                            $q2->where('product_id', $productId)
                                ->whereNull('variant_id');
                        });
                } else {
                    $q->where('product_id', $productId)
                        ->whereNull('variant_id');
                }
            })
            ->where(function ($q) use ($quantity) {
                $q->whereNull('min_quantity')->orWhere('min_quantity', '<=', $quantity);
            })
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('priority', 'desc');

        return $query->first();
    }

    private function applyRule(PriceRule $rule, string $basePrice, string $quantity): string
    {
        return match ($rule->pricing_type) {
            PricingType::Flat => bcadd($rule->value, '0', 8),
            PricingType::Percentage => bcadd(
                $basePrice,
                bcdiv(bcmul($basePrice, $rule->value, 8), '100', 8),
                8
            ),
            PricingType::Tiered => $this->applyTieredRule($rule, $basePrice, $quantity),
            PricingType::Conditional => $this->applyConditionalRule($rule, $basePrice),
            PricingType::RuleBased => bcadd($rule->value, '0', 8),
        };
    }

    /**
     * Apply a conditional pricing rule.
     *
     * The rule's `conditions` JSON may contain an `adjustment` object with:
     *   - type: 'flat' | 'percentage' | 'fixed'
     *   - value: numeric string
     * When no matching condition is found the base price is returned unchanged.
     */
    private function applyConditionalRule(PriceRule $rule, string $basePrice): string
    {
        $conditions = $rule->conditions ?? [];
        $adjustment = $conditions['adjustment'] ?? null;

        if (! $adjustment) {
            return bcadd($basePrice, '0', 8);
        }

        $adjustValue = (string) ($adjustment['value'] ?? '0');
        $adjustType = $adjustment['type'] ?? 'flat';

        return match ($adjustType) {
            'percentage' => bcadd(
                $basePrice,
                bcdiv(bcmul($basePrice, $adjustValue, 8), '100', 8),
                8
            ),
            'fixed' => bcadd($adjustValue, '0', 8),
            default => bcadd($basePrice, $adjustValue, 8), // flat delta on base price
        };
    }

    private function applyTieredRule(PriceRule $rule, string $basePrice, string $quantity): string
    {
        $tiers = $rule->tiers ?? [];

        foreach ($tiers as $tier) {
            $min = (string) ($tier['min_qty'] ?? '0');
            $max = isset($tier['max_qty']) ? (string) $tier['max_qty'] : null;

            $aboveMin = bccomp($quantity, $min, 8) >= 0;
            $belowMax = $max === null || bccomp($quantity, $max, 8) <= 0;

            if ($aboveMin && $belowMax) {
                return bcadd((string) $tier['price'], '0', 8);
            }
        }

        return bcadd($basePrice, '0', 8);
    }
}
