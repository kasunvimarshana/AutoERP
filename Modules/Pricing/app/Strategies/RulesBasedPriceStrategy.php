<?php

declare(strict_types=1);

namespace Modules\Pricing\Strategies;

use Modules\Pricing\Enums\RuleOperator;
use Modules\Pricing\Interfaces\PricingEngineInterface;
use Modules\Pricing\Repositories\PriceRuleRepository;
use Modules\Product\Models\Product;

/**
 * Rules-Based Price Strategy
 *
 * Conditional pricing based on dynamic rules
 */
class RulesBasedPriceStrategy implements PricingEngineInterface
{
    public function __construct(
        private readonly PriceRuleRepository $priceRuleRepository
    ) {}

    /**
     * Calculate price using rules-based pricing
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculatePrice(int $productId, string $quantity, array $context = []): array
    {
        $product = Product::findOrFail($productId);

        $basePrice = (string) $product->selling_price;
        $unitPrice = $basePrice;
        $appliedRules = [];

        // Get active rules ordered by priority
        $rules = $this->priceRuleRepository->getActiveRulesOrderedByPriority();

        foreach ($rules as $rule) {
            if ($this->evaluateConditions($rule->conditions ?? [], $context)) {
                $unitPrice = $this->applyActions($unitPrice, $rule->actions ?? []);
                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'priority' => $rule->priority,
                ];
            }
        }

        $subtotal = bcmul($unitPrice, $quantity, 2);

        return [
            'strategy' => $this->getStrategyName(),
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'currency' => $context['currency'] ?? 'USD',
            'breakdown' => [
                'base_price' => $basePrice,
                'applied_rules' => $appliedRules,
                'adjustments' => [],
            ],
        ];
    }

    /**
     * Evaluate rule conditions
     *
     * @param  array<int, mixed>  $conditions
     * @param  array<string, mixed>  $context
     */
    private function evaluateConditions(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (! $field || ! isset($context[$field])) {
                return false;
            }

            $operatorEnum = RuleOperator::from($operator);
            if (! $operatorEnum->evaluate($context[$field], $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply rule actions to price
     *
     * @param  array<int, mixed>  $actions
     */
    private function applyActions(string $price, array $actions): string
    {
        foreach ($actions as $action) {
            $type = $action['type'] ?? null;
            $value = $action['value'] ?? '0';

            $price = match ($type) {
                'add' => bcadd($price, $value, 2),
                'subtract' => bcsub($price, $value, 2),
                'multiply' => bcmul($price, $value, 2),
                'percentage_increase' => bcadd($price, bcmul($price, bcdiv($value, '100', 4), 2), 2),
                'percentage_decrease' => bcsub($price, bcmul($price, bcdiv($value, '100', 4), 2), 2),
                'set' => $value,
                default => $price,
            };
        }

        return $price;
    }

    /**
     * Get strategy name
     */
    public function getStrategyName(): string
    {
        return 'rules_based';
    }
}
