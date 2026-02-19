<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Pricing\Contracts\PricingEngineInterface;

/**
 * RuleBasedPricingEngine
 *
 * Metadata-driven rule engine for dynamic pricing
 * Supports conditional rules: if condition then action
 */
class RuleBasedPricingEngine implements PricingEngineInterface
{
    public function calculate(string $basePrice, string $quantity, array $context = []): string
    {
        $rules = $context['rules'] ?? [];

        if (empty($rules)) {
            return MathHelper::multiply($basePrice, $quantity);
        }

        $effectivePrice = $basePrice;

        foreach ($rules as $rule) {
            if ($this->evaluateCondition($rule['condition'] ?? [], $quantity, $context)) {
                $effectivePrice = $this->applyAction($effectivePrice, $rule['action'] ?? [], $context);

                if (($rule['stop_on_match'] ?? false)) {
                    break;
                }
            }
        }

        return MathHelper::multiply($effectivePrice, $quantity);
    }

    protected function evaluateCondition(array $condition, string $quantity, array $context): bool
    {
        if (empty($condition)) {
            return true;
        }

        $operator = $condition['operator'] ?? 'and';
        $conditions = $condition['conditions'] ?? [];

        if (empty($conditions)) {
            return $this->evaluateSingleCondition($condition, $quantity, $context);
        }

        if ($operator === 'or') {
            foreach ($conditions as $cond) {
                if ($this->evaluateSingleCondition($cond, $quantity, $context)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($conditions as $cond) {
            if (! $this->evaluateSingleCondition($cond, $quantity, $context)) {
                return false;
            }
        }

        return true;
    }

    protected function evaluateSingleCondition(array $condition, string $quantity, array $context): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return true;
        }

        $actualValue = $this->getFieldValue($field, $quantity, $context);

        if ($actualValue === null) {
            return false;
        }

        return match ($operator) {
            '=' => $actualValue === $value,
            '!=' => $actualValue !== $value,
            '>' => is_numeric($actualValue) && is_numeric($value) && MathHelper::greaterThan((string) $actualValue, (string) $value),
            '<' => is_numeric($actualValue) && is_numeric($value) && MathHelper::lessThan((string) $actualValue, (string) $value),
            '>=' => is_numeric($actualValue) && is_numeric($value) && MathHelper::compare((string) $actualValue, (string) $value) >= 0,
            '<=' => is_numeric($actualValue) && is_numeric($value) && MathHelper::compare((string) $actualValue, (string) $value) <= 0,
            'in' => is_array($value) && in_array($actualValue, $value),
            'not_in' => is_array($value) && ! in_array($actualValue, $value),
            'contains' => is_string($actualValue) && is_string($value) && str_contains($actualValue, $value),
            default => false,
        };
    }

    protected function getFieldValue(string $field, string $quantity, array $context): mixed
    {
        return match ($field) {
            'quantity' => $quantity,
            'location_id' => $context['location_id'] ?? null,
            'customer_id' => $context['customer_id'] ?? null,
            'customer_type' => $context['customer_type'] ?? null,
            default => $context[$field] ?? null,
        };
    }

    protected function applyAction(string $price, array $action, array $context): string
    {
        $type = $action['type'] ?? null;
        $value = $action['value'] ?? '0';

        return match ($type) {
            'set_price' => $value,
            'add' => MathHelper::add($price, $value),
            'subtract' => MathHelper::subtract($price, $value),
            'multiply' => MathHelper::multiply($price, $value),
            'percentage_increase' => MathHelper::add($price, MathHelper::percentage($price, $value)),
            'percentage_decrease' => MathHelper::subtract($price, MathHelper::percentage($price, $value)),
            default => $price,
        };
    }

    public function getStrategy(): string
    {
        return 'rule_based';
    }

    public function validate(array $config): bool
    {
        if (! isset($config['rules']) || ! is_array($config['rules'])) {
            return false;
        }

        foreach ($config['rules'] as $rule) {
            if (! isset($rule['condition']) || ! is_array($rule['condition'])) {
                return false;
            }

            if (! isset($rule['action']) || ! is_array($rule['action'])) {
                return false;
            }

            if (! isset($rule['action']['type'])) {
                return false;
            }
        }

        return true;
    }
}
