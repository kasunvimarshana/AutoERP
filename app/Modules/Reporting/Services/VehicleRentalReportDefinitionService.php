<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\DecimalMath;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Reporting\DTOs\ReportFilter;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalUsageFactStatus;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Models\RentalUsageFact;
use Modules\VehicleRental\Models\RentalUsageLog;

final class VehicleRentalReportDefinitionService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return list<ReportDefinition>
     */
    public function definitions(): array
    {
        return [
            $this->physicalRunningChart(),
            $this->driverOvertime(),
            $this->commercialRunningChart(RentalFinancialSide::Revenue),
            $this->commercialRunningChart(RentalFinancialSide::Cost),
        ];
    }

    private function physicalRunningChart(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-rental.running-chart',
            title: 'Daily Physical Running Chart',
            group: 'Vehicle Rental',
            model: RentalUsageLog::class,
            columns: [
                new ReportColumn('usage_date', 'Date', sortBy: 'usage_date', format: 'date'),
                new ReportColumn('usage_number', 'Running Chart', sortBy: 'usage_number'),
                new ReportColumn('vehicle', 'Vehicle', path: 'vehicle.registration_number'),
                new ReportColumn('allocation', 'Allocation', path: 'allocation.allocation_number'),
                new ReportColumn('driver', 'Driver', path: 'driver.display_name'),
                new ReportColumn('start_odometer', 'Start KM', sortBy: 'start_odometer', format: 'decimal'),
                new ReportColumn('end_odometer', 'Finish KM', sortBy: 'end_odometer', format: 'decimal'),
                new ReportColumn('distance_km', 'Physical KM', sortBy: 'distance_km', format: 'decimal', summarize: true),
                new ReportColumn('net_operational_distance_km', 'Net Operational KM', sortBy: 'net_operational_distance_km', format: 'decimal', summarize: true),
                new ReportColumn('garage_distance_km', 'Garage KM', sortBy: 'garage_distance_km', format: 'decimal', summarize: true),
                new ReportColumn('internal_distance_km', 'Internal KM', sortBy: 'internal_distance_km', format: 'decimal', summarize: true),
                new ReportColumn('status', 'Physical Status', sortBy: 'status', format: 'enum'),
            ],
            search: [
                'usage_number',
                'vehicle.registration_number',
                'allocation.allocation_number',
                'driver.display_name',
            ],
            relations: ['vehicle', 'allocation', 'driver'],
            filters: [$this->statusFilter(RentalUsageStatus::cases())],
            dateColumn: 'usage_date',
            defaultSort: 'usage_date',
            description: 'Actual vehicle movement and operational measurements. Customer and owner commercial facts are reported separately.',
            orientation: 'landscape',
        );
    }

    private function driverOvertime(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-rental.driver-overtime',
            title: 'Driver Overtime and Night-Out',
            group: 'Vehicle Rental',
            model: RentalUsageLog::class,
            columns: [
                new ReportColumn('usage_date', 'Date', sortBy: 'usage_date', format: 'date'),
                new ReportColumn('usage_number', 'Running Chart', sortBy: 'usage_number'),
                new ReportColumn('driver', 'Driver', path: 'driver.display_name'),
                new ReportColumn('vehicle', 'Vehicle', path: 'vehicle.registration_number'),
                new ReportColumn('allocation', 'Allocation', path: 'allocation.allocation_number'),
                new ReportColumn(
                    'normal_ot_hours',
                    'Normal OT Hours',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageLog $usage): string => $this->math->div((string) $usage->normal_overtime_minutes, '60'),
                ),
                new ReportColumn(
                    'double_ot_hours',
                    'Double OT Hours',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageLog $usage): string => $this->math->div((string) $usage->double_overtime_minutes, '60'),
                ),
                new ReportColumn(
                    'triple_ot_hours',
                    'Triple OT Hours',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageLog $usage): string => $this->math->div((string) $usage->triple_overtime_minutes, '60'),
                ),
                new ReportColumn('night_out_count', 'Night-Outs', sortBy: 'night_out_count', format: 'decimal', summarize: true),
            ],
            search: [
                'usage_number',
                'driver.display_name',
                'vehicle.registration_number',
                'allocation.allocation_number',
            ],
            relations: ['driver', 'vehicle', 'allocation'],
            dateColumn: 'usage_date',
            defaultSort: 'usage_date',
            scope: static fn (Builder $query): Builder => $query
                ->where('status', RentalUsageStatus::Approved->value)
                ->where(static fn (Builder $hours): Builder => $hours
                    ->where('normal_overtime_minutes', '>', 0)
                    ->orWhere('double_overtime_minutes', '>', 0)
                    ->orWhere('triple_overtime_minutes', '>', 0)
                    ->orWhere('night_out_count', '>', 0)),
            description: 'Approved physical driver overtime and night-out measurements. Payroll entitlement remains owned by HR and Payroll.',
            orientation: 'landscape',
        );
    }

    private function commercialRunningChart(RentalFinancialSide $side): ReportDefinition
    {
        $revenue = $side === RentalFinancialSide::Revenue;

        return new ReportDefinition(
            key: $revenue
                ? 'vehicle-rental.customer-running-chart'
                : 'vehicle-rental.owner-running-chart',
            title: $revenue
                ? 'Customer Billable Running Chart'
                : 'Vehicle Owner Payable Running Chart',
            group: 'Vehicle Rental',
            model: RentalUsageFact::class,
            columns: [
                new ReportColumn('usage_date', 'Date', path: 'usageLog.usage_date', sortBy: 'started_at', format: 'date'),
                new ReportColumn('usage_number', 'Running Chart', path: 'usageLog.usage_number'),
                new ReportColumn('agreement', $revenue ? 'Customer Agreement' : 'Owner Agreement', path: 'context.agreement.agreement_number'),
                new ReportColumn('party', $revenue ? 'Customer / Lessee' : 'Vehicle Owner / Lessor', path: $revenue
                    ? 'context.agreement.customer.display_name'
                    : 'context.agreement.supplier.display_name'),
                new ReportColumn('vehicle', 'Vehicle', path: 'usageLog.vehicle.registration_number'),
                new ReportColumn('driver', 'Driver', path: 'usageLog.driver.display_name'),
                new ReportColumn('started_at', 'Commercial Start', sortBy: 'started_at', format: 'datetime'),
                new ReportColumn('ended_at', 'Commercial Finish', sortBy: 'ended_at', format: 'datetime'),
                new ReportColumn('start_odometer', 'Commercial Start KM', sortBy: 'start_odometer', format: 'decimal'),
                new ReportColumn('end_odometer', 'Commercial Finish KM', sortBy: 'end_odometer', format: 'decimal'),
                new ReportColumn(
                    'commercial_distance_km',
                    $revenue ? 'Billable KM' : 'Payable KM',
                    sortBy: 'commercial_distance_km',
                    format: 'decimal',
                    summarize: true,
                ),
                new ReportColumn(
                    'normal_ot_hours',
                    'Normal OT Hours',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageFact $fact): string => $this->math->div((string) $fact->normal_overtime_minutes, '60'),
                ),
                new ReportColumn(
                    'double_ot_hours',
                    'Double OT Hours',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageFact $fact): string => $this->math->div((string) $fact->double_overtime_minutes, '60'),
                ),
                new ReportColumn(
                    'triple_ot_hours',
                    'Triple OT Hours',
                    format: 'decimal',
                    summarize: true,
                    value: fn (RentalUsageFact $fact): string => $this->math->div((string) $fact->triple_overtime_minutes, '60'),
                ),
                new ReportColumn('night_out_count', 'Night-Outs', sortBy: 'night_out_count', format: 'decimal', summarize: true),
                new ReportColumn('reference_number', 'Customer / Owner Reference', sortBy: 'reference_number'),
                new ReportColumn('variance_reason', 'Variance Reason'),
                new ReportColumn('status', 'Commercial Status', sortBy: 'status', format: 'enum'),
            ],
            search: ['reference_number', 'usageLog.usage_number'],
            relations: [
                'usageLog.vehicle',
                'usageLog.driver',
                'context.agreement.customer',
                'context.agreement.supplier',
            ],
            filters: [$this->statusFilter(RentalUsageFactStatus::cases())],
            dateColumn: 'usageLog.usage_date',
            defaultSort: 'started_at',
            constraints: ['financial_side' => $side->value],
            description: $revenue
                ? 'Customer-approved billable usage facts, independent from physical movement and owner settlement.'
                : 'Vehicle-owner-approved payable usage facts, independent from physical movement and customer billing.',
            orientation: 'landscape',
        );
    }

    /**
     * @param list<RentalUsageStatus|RentalUsageFactStatus> $statuses
     */
    private function statusFilter(array $statuses): ReportFilter
    {
        return new ReportFilter(
            key: 'status',
            label: 'Status',
            field: 'status',
            type: 'select',
            options: array_map(
                static fn (RentalUsageStatus|RentalUsageFactStatus $status): array => [
                    'value' => $status->value,
                    'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                ],
                $statuses,
            ),
        );
    }
}
