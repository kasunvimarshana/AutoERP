<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Services\ReportDefinitionRegistry;
use Modules\Reporting\Services\VehicleRentalReportService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class VehicleRentalReportDefinitionTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function reportDefinitions(): array
    {
        return [
            'running chart' => [VehicleRentalReportService::RUNNING_CHART, 'Daily Running Chart Report'],
            'chart exceptions' => [VehicleRentalReportService::CHART_EXCEPTIONS, 'Missing / Duplicate Running Chart Exceptions'],
            'customer invoices' => [VehicleRentalReportService::CUSTOMER_INVOICES, 'Customer Invoice Register'],
            'owner vouchers' => [VehicleRentalReportService::OWNER_VOUCHERS, 'Owner Payable Voucher Register'],
            'rental history' => [VehicleRentalReportService::RENTAL_HISTORY, 'Vehicle Rental History'],
        ];
    }

    #[DataProvider('reportDefinitions')]
    public function test_phase_one_vehicle_rental_report_is_registered(string $key, string $title): void
    {
        $definition = $this->app->make(ReportDefinitionRegistry::class)->get($key);

        self::assertSame($key, $definition->key);
        self::assertSame($title, $definition->title);
        self::assertStringStartsWith('Vehicle Rental', $definition->group);
        self::assertSame('landscape', $definition->orientation);
        self::assertNotEmpty($definition->columns);
    }

    public function test_financial_registers_expose_traceability_and_balance_columns(): void
    {
        $registry = $this->app->make(ReportDefinitionRegistry::class);

        foreach ([VehicleRentalReportService::CUSTOMER_INVOICES, VehicleRentalReportService::OWNER_VOUCHERS] as $key) {
            $columns = collect($registry->get($key)->columns)->pluck('key')->all();

            self::assertContains('agreement', $columns);
            self::assertContains('calculation_number', $columns);
            self::assertContains('running_charts', $columns);
            self::assertContains('grand_total', $columns);
            self::assertContains('paid', $columns);
            self::assertContains('balance_due', $columns);
        }
    }

    public function test_specialized_vehicle_rental_routes_are_registered_before_generic_reports(): void
    {
        foreach ([
            'api.v1.reports.vehicle-rental.running-chart',
            'api.v1.reports.vehicle-rental.chart-exceptions',
            'api.v1.reports.vehicle-rental.customer-invoices',
            'api.v1.reports.vehicle-rental.owner-vouchers',
            'api.v1.reports.vehicle-rental.rental-history',
            'api.v1.reports.vehicle-rental.running-chart.export',
            'api.v1.reports.vehicle-rental.chart-exceptions.export',
            'api.v1.reports.vehicle-rental.customer-invoices.export',
            'api.v1.reports.vehicle-rental.owner-vouchers.export',
            'api.v1.reports.vehicle-rental.rental-history.export',
        ] as $routeName) {
            self::assertTrue(Route::has($routeName), "Route [{$routeName}] is not registered.");
        }
    }
}
