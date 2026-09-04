<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Modules\Reporting\Services\ReportDefinitionRegistry;
use Modules\Reporting\Services\ReportQueryBuilder;
use Modules\Reporting\Services\ReportSummaryBuilder;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;
use Tests\TestCase;

final class VehicleServiceProfitabilityReportTest extends TestCase
{
    public function test_cancelled_job_remains_visible_but_has_no_effective_profitability_contribution(): void
    {
        $job = $this->job(VehicleServiceJobStatus::Cancelled);
        $definition = app(ReportDefinitionRegistry::class)->get('vehicle-service.profitability');
        $row = app(ReportQueryBuilder::class)->row($definition, $job);
        $summary = app(ReportSummaryBuilder::class)->build($definition, [$row]);

        $this->assertSame('cancelled', $row['status']);
        foreach (['revenue', 'direct_cost', 'commission', 'gross_profit', 'margin'] as $metric) {
            $this->assertSame('0.000000', $row[$metric]);
        }
        $this->assertSame('0.000000', $this->summaryValue($summary, 'Total Revenue'));
        $this->assertSame('0.000000', $this->summaryValue($summary, 'Total Direct Cost'));
        $this->assertSame('0.000000', $this->summaryValue($summary, 'Total Commission'));
        $this->assertSame('0.000000', $this->summaryValue($summary, 'Total Gross Profit'));

        // Cancellation changes the effective report view, never the historical job values.
        $this->assertSame('1000.000000', $job->grand_total);
        $this->assertSame('50.000000', $job->supervisor_commission_amount);
        $this->assertSame('200.000000', $job->lines->sole()->unit_cost);
        $this->assertSame('100.000000', $job->employeeAssignments->sole()->commission_amount);
    }

    public function test_active_job_profitability_calculation_is_unchanged(): void
    {
        $definition = app(ReportDefinitionRegistry::class)->get('vehicle-service.profitability');
        $row = app(ReportQueryBuilder::class)->row($definition, $this->job(VehicleServiceJobStatus::Completed));

        $this->assertSame('1000.000000', $row['revenue']);
        $this->assertSame('400.000000', $row['direct_cost']);
        $this->assertSame('150.000000', $row['commission']);
        $this->assertSame('450.000000', $row['gross_profit']);
        $this->assertSame('45.000000', $row['margin']);
    }

    private function job(VehicleServiceJobStatus $status): VehicleServiceJob
    {
        $job = new VehicleServiceJob;
        $job->forceFill([
            'status' => $status->value,
            'grand_total' => '1000.000000',
            'supervisor_commission_amount' => '50.000000',
        ]);

        $line = new VehicleServiceJobLine([
            'quantity' => '2.000000',
            'unit_cost' => '200.000000',
        ]);
        $assignment = new VehicleServiceLineEmployee([
            'commission_amount' => '100.000000',
        ]);
        $job->setRelation('lines', collect([$line]));
        $job->setRelation('employeeAssignments', collect([$assignment]));

        return $job;
    }

    /**
     * @param  array<int, array{label: string, value: mixed, format: string}>  $summary
     */
    private function summaryValue(array $summary, string $label): mixed
    {
        foreach ($summary as $entry) {
            if ($entry['label'] === $label) {
                return $entry['value'];
            }
        }

        $this->fail("Missing report summary: {$label}");
    }
}
