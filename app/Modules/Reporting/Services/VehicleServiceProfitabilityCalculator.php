<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\Models\VehicleServiceJob;
use WeakMap;

final class VehicleServiceProfitabilityCalculator
{
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

        return $this->cache[$job][$metric] ?? '0.000000';
    }

    /**
     * @return array<string, string>
     */
    private function calculate(VehicleServiceJob $job): array
    {
        $revenue = $this->math->normalize((string) ($job->grand_total ?? '0'));
        $directCost = '0.000000';

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
            ? '0.000000'
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
