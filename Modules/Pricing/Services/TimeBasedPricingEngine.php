<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use Carbon\Carbon;
use Modules\Core\Helpers\MathHelper;
use Modules\Pricing\Contracts\PricingEngineInterface;

/**
 * TimeBasedPricingEngine
 *
 * Time-based pricing with day of week, hour ranges, and date ranges
 */
class TimeBasedPricingEngine implements PricingEngineInterface
{
    public function calculate(string $basePrice, string $quantity, array $context = []): string
    {
        $rules = $context['rules'] ?? [];
        $date = isset($context['date']) ? Carbon::parse($context['date']) : now();

        if (empty($rules)) {
            return MathHelper::multiply($basePrice, $quantity);
        }

        $applicableRule = $this->findApplicableRule($rules, $date);

        if (! $applicableRule) {
            return MathHelper::multiply($basePrice, $quantity);
        }

        $effectivePrice = $this->calculateEffectivePrice($basePrice, $applicableRule);

        return MathHelper::multiply($effectivePrice, $quantity);
    }

    protected function findApplicableRule(array $rules, Carbon $date): ?array
    {
        foreach ($rules as $rule) {
            if ($this->ruleApplies($rule, $date)) {
                return $rule;
            }
        }

        return null;
    }

    protected function ruleApplies(array $rule, Carbon $date): bool
    {
        if (isset($rule['day_of_week'])) {
            $daysOfWeek = is_array($rule['day_of_week']) ? $rule['day_of_week'] : [$rule['day_of_week']];
            if (! in_array($date->dayOfWeek, $daysOfWeek)) {
                return false;
            }
        }

        if (isset($rule['hour_start']) && isset($rule['hour_end'])) {
            $hour = $date->hour;
            $hourStart = (int) $rule['hour_start'];
            $hourEnd = (int) $rule['hour_end'];

            if ($hour < $hourStart || $hour > $hourEnd) {
                return false;
            }
        }

        if (isset($rule['date_start'])) {
            $dateStart = Carbon::parse($rule['date_start']);
            if ($date->lt($dateStart)) {
                return false;
            }
        }

        if (isset($rule['date_end'])) {
            $dateEnd = Carbon::parse($rule['date_end']);
            if ($date->gt($dateEnd)) {
                return false;
            }
        }

        return true;
    }

    protected function calculateEffectivePrice(string $basePrice, array $rule): string
    {
        if (isset($rule['price'])) {
            return $rule['price'];
        }

        if (isset($rule['adjustment_percentage'])) {
            $adjustment = MathHelper::percentage($basePrice, $rule['adjustment_percentage']);

            return MathHelper::add($basePrice, $adjustment);
        }

        return $basePrice;
    }

    public function getStrategy(): string
    {
        return 'time_based';
    }

    public function validate(array $config): bool
    {
        if (! isset($config['rules']) || ! is_array($config['rules'])) {
            return false;
        }

        foreach ($config['rules'] as $rule) {
            if (! isset($rule['price']) && ! isset($rule['adjustment_percentage'])) {
                return false;
            }

            if (isset($rule['hour_start']) && ! is_numeric($rule['hour_start'])) {
                return false;
            }

            if (isset($rule['hour_end']) && ! is_numeric($rule['hour_end'])) {
                return false;
            }
        }

        return true;
    }
}
