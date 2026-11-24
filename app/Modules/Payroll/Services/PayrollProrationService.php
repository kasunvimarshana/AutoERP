<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Services;

use Carbon\CarbonImmutable;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\Models\PayrollPayPeriod;

final class PayrollProrationService
{
    /**
     * @return array{
     *     is_eligible: bool,
     *     eligible_days: string,
     *     standard_days: string,
     *     proration_factor: string,
     *     proration_reason: ?string
     * }
     */
    public function calculateProration(HrEmployee $employee, PayrollPayPeriod $period): array
    {
        $periodStart = CarbonImmutable::parse($period->period_start);
        $periodEnd = CarbonImmutable::parse($period->period_end);
        $joinedDate = $employee->joined_date !== null ? CarbonImmutable::parse($employee->joined_date) : null;
        $resignedDate = $employee->resigned_date !== null ? CarbonImmutable::parse($employee->resigned_date) : null;

        // If joined after period end or resigned before period start -> Not eligible
        if ($joinedDate !== null && $joinedDate->isAfter($periodEnd)) {
            return [
                'is_eligible' => false,
                'eligible_days' => '0.00',
                'standard_days' => (string) $period->total_working_days,
                'proration_factor' => '0.0000',
                'proration_reason' => 'Joined after pay period end',
            ];
        }

        if ($resignedDate !== null && $resignedDate->isBefore($periodStart)) {
            return [
                'is_eligible' => false,
                'eligible_days' => '0.00',
                'standard_days' => (string) $period->total_working_days,
                'proration_factor' => '0.0000',
                'proration_reason' => 'Resigned before pay period start',
            ];
        }

        $standardDays = max(1, (int) $period->total_working_days);

        // Effective start and end within period
        $effectiveStart = ($joinedDate !== null && $joinedDate->isAfter($periodStart)) ? $joinedDate : $periodStart;
        $effectiveEnd = ($resignedDate !== null && $resignedDate->isBefore($periodEnd)) ? $resignedDate : $periodEnd;

        $isMidJoiner = $joinedDate !== null && $joinedDate->isAfter($periodStart);
        $isMidLeaver = $resignedDate !== null && $resignedDate->isBefore($periodEnd);

        if (! $isMidJoiner && ! $isMidLeaver) {
            return [
                'is_eligible' => true,
                'eligible_days' => sprintf('%.2f', $standardDays),
                'standard_days' => sprintf('%.2f', $standardDays),
                'proration_factor' => '1.0000',
                'proration_reason' => null,
            ];
        }

        // Count civil working days (Monday to Friday) between effectiveStart and effectiveEnd
        $activeDays = 0;
        $cursor = $effectiveStart;
        while ($cursor->isBefore($effectiveEnd) || $cursor->isSameDay($effectiveEnd)) {
            if (! $cursor->isWeekend()) {
                $activeDays++;
            }
            $cursor = $cursor->addDay();
        }

        $cappedActiveDays = min($activeDays, $standardDays);
        $factor = bcdiv((string) $cappedActiveDays, (string) $standardDays, 4);

        $reason = $isMidJoiner && $isMidLeaver
            ? 'Mid-period joiner & leaver'
            : ($isMidJoiner ? 'Mid-period joiner' : 'Mid-period leaver');

        return [
            'is_eligible' => true,
            'eligible_days' => sprintf('%.2f', $cappedActiveDays),
            'standard_days' => sprintf('%.2f', $standardDays),
            'proration_factor' => $factor,
            'proration_reason' => $reason,
        ];
    }
}
