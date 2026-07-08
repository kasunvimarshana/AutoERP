<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class VehicleFinanceProductionReadinessContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_vehicle_finance_due_status_refresh_is_scheduled_and_lock_safe(): void
    {
        $financeService = $this->source('app/Modules/VehicleRental/Services/VehicleFinanceService.php');
        $refreshService = $this->source('app/Modules/VehicleRental/Services/VehicleFinanceInstallmentStatusRefreshService.php');
        $command = $this->source('app/Modules/VehicleRental/Console/Commands/VehicleFinanceRefreshDueStatusesCommand.php');
        $provider = $this->source('app/Modules/VehicleRental/Providers/VehicleRentalServiceProvider.php');
        $console = $this->source('routes/console.php');
        $controller = $this->source('app/Modules/VehicleRental/Http/Controllers/VehicleFinanceController.php');

        self::assertStringNotContainsString('function refreshDueStatuses', $financeService);
        self::assertStringContainsString('VehicleFinanceInstallmentStatusRefreshService', $controller);
        self::assertStringContainsString('VehicleFinanceInstallmentStatusRefreshService $service', $controller);
        self::assertStringContainsString('DB::transaction', $refreshService);
        self::assertGreaterThanOrEqual(2, substr_count($refreshService, '->lockForUpdate()'));
        self::assertStringContainsString('VehicleFinanceStatusHistory::query()->create', $refreshService);
        self::assertStringContainsString('VehicleFinanceRefreshDueStatusesCommand::class', $provider);
        self::assertStringContainsString("vehicle-rental:finance-installments:refresh-due", $command);
        self::assertStringContainsString("Schedule::command('vehicle-rental:finance-installments:refresh-due')", $console);
        self::assertStringContainsString('->hourly()', $console);
        self::assertStringContainsString('->withoutOverlapping()', $console);
        self::assertStringContainsString('->onOneServer()', $console);
    }

    public function test_vehicle_finance_payable_action_uses_financial_document_permission(): void
    {
        $financePage = $this->source('resources/js/modules/vehicle-rental/pages/VehicleFinancePage.tsx');

        self::assertStringContainsString('const canCreateDocument = hasPermission(', $financePage);
        self::assertStringContainsString('vehicleRentalPermissions.financialCreate', $financePage);
        self::assertStringContainsString('canCreateDocument && !row.invoice', $financePage);
        self::assertStringContainsString('canManage && row.status === "draft"', $financePage);
        self::assertStringNotContainsString('canManage && !row.invoice', $financePage);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
