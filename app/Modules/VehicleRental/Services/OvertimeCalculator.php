<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;

final class OvertimeCalculator
{
    public const DEFAULT_BASELINE_HOURS = 8.0;

    public const MINUTES_PER_HOUR = 60;

    /**
     * Calculate overtime minutes from trip duration and baseline agreed working hours.
     *
     * @param CarbonImmutable|string|null $startsAt
     * @param CarbonImmutable|string|null $endsAt
     * @param int|float|string|null $baselineHours
     * @return int Overtime in minutes (0 if within baseline or invalid)
     */
    public static function calculateOtMinutes(
        CarbonImmutable|string|null $startsAt = null,
        CarbonImmutable|string|null $endsAt = null,
        int|float|string|null $baselineHours = self::DEFAULT_BASELINE_HOURS,
    ): int {
        if ($startsAt === null || $endsAt === null || $baselineHours === null) {
            return 0;
        }

        $baseline = (float) $baselineHours;
        if ($baseline <= 0) {
            return 0;
        }

        try {
            $start = $startsAt instanceof CarbonImmutable ? $startsAt : CarbonImmutable::parse($startsAt);
            $end = $endsAt instanceof CarbonImmutable ? $endsAt : CarbonImmutable::parse($endsAt);
        } catch (\Throwable) {
            return 0;
        }

        if ($end <= $start) {
            return 0;
        }

        $tripMinutes = (int) $start->diffInMinutes($end);
        $baselineMinutes = (int) round($baseline * self::MINUTES_PER_HOUR);

        return max(0, $tripMinutes - $baselineMinutes);
    }

    /**
     * Format minutes into human-readable hours and minutes string (e.g. "2 hrs 30 mins").
     */
    public static function formatMinutes(?int $totalMinutes): string
    {
        if ($totalMinutes === null || $totalMinutes <= 0) {
            return '0 mins';
        }

        $hours = intdiv($totalMinutes, self::MINUTES_PER_HOUR);
        $minutes = $totalMinutes % self::MINUTES_PER_HOUR;

        if ($hours === 0) {
            return "{$minutes} min" . ($minutes !== 1 ? 's' : '');
        }

        if ($minutes === 0) {
            return "{$hours} hr" . ($hours !== 1 ? 's' : '');
        }

        return "{$hours} hr" . ($hours !== 1 ? 's' : '') . " {$minutes} min" . ($minutes !== 1 ? 's' : '');
    }
}
