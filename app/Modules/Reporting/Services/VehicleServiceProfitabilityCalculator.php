<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use WeakMap;

final class VehicleServiceProfitabilityCalculator
{
    private const ZERO_AMOUNT = '0.000000';

    /** @var WeakMap<VehicleServiceJob, array<string, string>> */
    private WeakMap $cache;

    public function __construct(private readonly DecimalMath $math)
    {
        $this->cache = new WeakMap;
    }

    public function value(VehicleServiceJob $job, string $metric): string
    {
        if (! isset($this->cache[$job])) {
            $this->cache[$job] = $this->calculate($job);
        }

        return $this->cache[$job][$metric] ?? self::ZERO_AMOUNT;
    }

    /**
     * @return array<string, string>
     */
    private function calculate(VehicleServiceJob $job): array
    {
        if ($job->status === VehicleServiceJobStatus::Cancelled) {
            return [
                'revenue' => self::ZERO_AMOUNT,
                'direct_cost' => self::ZERO_AMOUNT,
                'commission' => self::ZERO_AMOUNT,
                'gross_profit' => self::ZERO_AMOUNT,
                'margin' => self::ZERO_AMOUNT,
            ];
        }

        $revenue = $this->math->normalize((string) ($job->grand_total ?? '0'));
        $directCost = self::ZERO_AMOUNT;

        foreach ($job->lines as $line) {
            $directCost = $this->math->add(
                $directCost,
                $this->math->mul((string) ($line->quantity ?? '0'), (string) ($line->unit_cost ?? '0')),
            );
        }

        $commission = $this->math->normalize((string) ($job->supervisor_commission_amount ?? '0'));
        foreach ($job->employeeAssignments as $assignment) {
            $commission = $this->math->add($commission, (string) ($assignment->commission_amount ?? '0'));
        }

        $profit = $this->math->sub($this->math->sub($revenue, $directCost), $commission);
        $margin = $this->math->compare($revenue, '0') === 0
            ? self::ZERO_AMOUNT
            : $this->math->mul($this->math->div($profit, $revenue), '100');

        return [
            'revenue' => $revenue,
            'direct_cost' => $directCost,
            'commission' => $commission,
            'gross_profit' => $profit,
            'margin' => $margin,
        ];
    }
}
