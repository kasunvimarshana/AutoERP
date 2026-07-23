<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use InvalidArgumentException;
use Modules\Invoice\Models\Invoice;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Models\RentalRunningChart;

final class VehicleRentalReportDefinitions
{
    /** @return list<ReportDefinition> */
    public function all(): array
    {
        return [
            $this->runningChart(),
            $this->chartExceptions(),
            $this->customerInvoices(),
            $this->ownerVouchers(),
            $this->rentalHistory(),
        ];
    }

    public function get(string $key): ReportDefinition
    {
        return match ($key) {
            VehicleRentalReportService::RUNNING_CHART => $this->runningChart(),
            VehicleRentalReportService::CHART_EXCEPTIONS => $this->chartExceptions(),
            VehicleRentalReportService::CUSTOMER_INVOICES => $this->customerInvoices(),
            VehicleRentalReportService::OWNER_VOUCHERS => $this->ownerVouchers(),
            VehicleRentalReportService::RENTAL_HISTORY => $this->rentalHistory(),
            default => throw new InvalidArgumentException("Vehicle Rental report [{$key}] is not defined."),
        };
    }

    private function runningChart(): ReportDefinition
    {
        return new ReportDefinition(
            key: VehicleRentalReportService::RUNNING_CHART,
            title: 'Daily Running Chart Report',
            group: 'Vehicle Rental — Operations',
            model: RentalRunningChart::class,
            columns: [
                new ReportColumn('operational_date', 'Date', sortBy: 'operational_date', format: 'date'),
                new ReportColumn('chart_number', 'Running Chart', sortBy: 'chart_number'),
                new ReportColumn('status', 'Status', sortBy: 'status'),
                new ReportColumn('customer', 'Customer', sortBy: 'customer'),
                new ReportColumn('customer_agreement', 'Customer Agreement'),
                new ReportColumn('owner', 'Vehicle Owner'),
                new ReportColumn('owner_agreement', 'Owner Agreement'),
                new ReportColumn('vehicle', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('driver', 'Driver'),
                new ReportColumn('starts_at', 'Start Time', format: 'datetime'),
                new ReportColumn('ends_at', 'End Time', format: 'datetime'),
                new ReportColumn('start_odometer', 'Start KM', format: 'decimal'),
                new ReportColumn('end_odometer', 'End KM', format: 'decimal'),
                new ReportColumn('total_km', 'Total KM', format: 'decimal', summarize: true),
                new ReportColumn('garage_km', 'Garage KM', format: 'decimal', summarize: true),
                new ReportColumn('commercial_km', 'Commercial KM', sortBy: 'commercial_km', format: 'decimal', summarize: true),
                new ReportColumn('normal_ot_hours', 'Normal OT', format: 'decimal', summarize: true),
                new ReportColumn('double_ot_hours', 'Double OT', format: 'decimal', summarize: true),
                new ReportColumn('triple_ot_hours', 'Triple OT', format: 'decimal', summarize: true),
                new ReportColumn('night_outs', 'Night-Outs', format: 'decimal', summarize: true),
                new ReportColumn('origin', 'Origin'),
                new ReportColumn('destination', 'Destination'),
                new ReportColumn('purpose', 'Purpose'),
                new ReportColumn('replaces_chart', 'Replaces Chart'),
                new ReportColumn('remarks', 'Remarks'),
            ],
            dateColumn: 'operational_date',
            defaultSort: 'operational_date',
            defaultDirection: 'desc',
            description: 'Physical daily movement with customer, owner, vehicle, driver, kilometre and overtime context.',
            orientation: 'landscape',
        );
    }

    private function chartExceptions(): ReportDefinition
    {
        return new ReportDefinition(
            key: VehicleRentalReportService::CHART_EXCEPTIONS,
            title: 'Missing / Duplicate Running Chart Exceptions',
            group: 'Vehicle Rental — Operations',
            model: RentalAssignment::class,
            columns: [
                new ReportColumn('operational_date', 'Date', sortBy: 'operational_date', format: 'date'),
                new ReportColumn('exception_type', 'Exception', sortBy: 'exception_type'),
                new ReportColumn('vehicle', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('assignment', 'Assignment'),
                new ReportColumn('customer', 'Customer', sortBy: 'customer'),
                new ReportColumn('customer_agreement', 'Customer Agreement'),
                new ReportColumn('owner', 'Vehicle Owner'),
                new ReportColumn('owner_agreement', 'Owner Agreement'),
                new ReportColumn('chart_count', 'Charts', sortBy: 'chart_count', format: 'decimal'),
                new ReportColumn('chart_numbers', 'Chart Numbers'),
                new ReportColumn('explanation', 'Explanation'),
            ],
            dateColumn: 'operational_date',
            defaultSort: 'operational_date',
            defaultDirection: 'asc',
            description: 'Past and current assignment dates with missing charts or duplicate current chart evidence. A date range is required.',
            orientation: 'landscape',
        );
    }

    private function customerInvoices(): ReportDefinition
    {
        return $this->financialDocument(
            VehicleRentalReportService::CUSTOMER_INVOICES,
            'Customer Invoice Register',
            'Posted and reversed Vehicle Rental customer invoices traced to their calculations and Running Charts.',
        );
    }

    private function ownerVouchers(): ReportDefinition
    {
        return $this->financialDocument(
            VehicleRentalReportService::OWNER_VOUCHERS,
            'Owner Payable Voucher Register',
            'Posted and reversed self-billed Vehicle Rental owner settlements traced to their calculations and Running Charts.',
        );
    }

    private function financialDocument(string $key, string $title, string $description): ReportDefinition
    {
        return new ReportDefinition(
            key: $key,
            title: $title,
            group: 'Vehicle Rental — Financial',
            model: Invoice::class,
            columns: [
                new ReportColumn('document_date', 'Document Date', sortBy: 'document_date', format: 'date'),
                new ReportColumn('document_number', $key === VehicleRentalReportService::CUSTOMER_INVOICES ? 'Invoice No.' : 'Voucher No.', sortBy: 'document_number'),
                new ReportColumn('status', 'Status', sortBy: 'status'),
                new ReportColumn('party', $key === VehicleRentalReportService::CUSTOMER_INVOICES ? 'Customer' : 'Vehicle Owner', sortBy: 'party'),
                new ReportColumn('agreement', 'Agreement', sortBy: 'agreement'),
                new ReportColumn('vehicle', 'Vehicle'),
                new ReportColumn('period_start', 'Period From', sortBy: 'period_start', format: 'date'),
                new ReportColumn('period_end', 'Period To', sortBy: 'period_end', format: 'date'),
                new ReportColumn('calculation_number', 'Calculation'),
                new ReportColumn('running_charts', 'Charts', format: 'decimal'),
                new ReportColumn('operating_days', 'Operating Days', format: 'decimal'),
                new ReportColumn('commercial_km', 'Commercial KM', format: 'decimal', summarize: true),
                new ReportColumn('excess_km', 'Excess KM', format: 'decimal', summarize: true),
                new ReportColumn('subtotal', 'Subtotal', format: 'money', summarize: true),
                new ReportColumn('tax', 'Tax', format: 'money', summarize: true),
                new ReportColumn('adjustments', 'Adjustments', format: 'money', summarize: true),
                new ReportColumn('grand_total', $key === VehicleRentalReportService::CUSTOMER_INVOICES ? 'Invoice Total' : 'Net Payable', sortBy: 'grand_total', format: 'money', summarize: true),
                new ReportColumn('paid', 'Settled', sortBy: 'paid', format: 'money', summarize: true),
                new ReportColumn('balance_due', 'Outstanding', sortBy: 'balance_due', format: 'money', summarize: true),
                new ReportColumn('currency', 'Currency'),
                new ReportColumn('due_date', 'Due Date', format: 'date'),
            ],
            dateColumn: 'document_date',
            defaultSort: 'document_date',
            defaultDirection: 'desc',
            description: $description,
            orientation: 'landscape',
        );
    }

    private function rentalHistory(): ReportDefinition
    {
        return new ReportDefinition(
            key: VehicleRentalReportService::RENTAL_HISTORY,
            title: 'Vehicle Rental History',
            group: 'Vehicle Rental — Operations',
            model: RentalAssignment::class,
            columns: [
                new ReportColumn('assignment', 'Assignment', sortBy: 'assignment'),
                new ReportColumn('starts_at', 'Start', sortBy: 'starts_at', format: 'datetime'),
                new ReportColumn('ends_at', 'End', sortBy: 'ends_at', format: 'datetime'),
                new ReportColumn('status', 'Status', sortBy: 'status'),
                new ReportColumn('customer', 'Customer', sortBy: 'customer'),
                new ReportColumn('customer_agreement', 'Customer Agreement'),
                new ReportColumn('owner', 'Vehicle Owner', sortBy: 'owner'),
                new ReportColumn('owner_agreement', 'Owner Agreement'),
                new ReportColumn('vehicle', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('driver_mode', 'Driver / Mode'),
                new ReportColumn('handover_odometer', 'Handover KM', format: 'decimal'),
                new ReportColumn('return_odometer', 'Return KM', format: 'decimal'),
                new ReportColumn('replaced_vehicle', 'Replaced Vehicle'),
                new ReportColumn('replacement_reason', 'Replacement Reason'),
                new ReportColumn('running_charts', 'Finalized Charts', format: 'decimal'),
                new ReportColumn('total_km', 'Total KM', format: 'decimal', summarize: true),
                new ReportColumn('garage_km', 'Garage KM', format: 'decimal', summarize: true),
                new ReportColumn('commercial_km', 'Commercial KM', sortBy: 'commercial_km', format: 'decimal', summarize: true),
            ],
            dateColumn: 'starts_at',
            defaultSort: 'starts_at',
            defaultDirection: 'desc',
            description: 'Customer-use assignment history with owner source, driver mode, replacement lineage and finalized Running Chart totals.',
            orientation: 'landscape',
        );
    }
}
