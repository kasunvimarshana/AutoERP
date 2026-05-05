<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Modules\Inventory\Domain\RepositoryInterfaces\CostLayerRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\ValuationConfigRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Architecture guardrail — all Inventory repository interfaces must declare
 * int $tenantId as the first parameter on every method that exposes or mutates
 * tenant-scoped data. All Eloquent implementations must apply ->where('tenant_id', …)
 * in those queries. Update-by-id methods that bypass the global tenant scope must
 * explicitly guard with where('tenant_id', …) to prevent cross-tenant writes.
 */
class InventoryRepositoryTenantGuardrailsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function assertFirstParamIsIntTenantId(string $class, string $method): void
    {
        $rm = new \ReflectionMethod($class, $method);
        $params = $rm->getParameters();
        $this->assertNotEmpty($params, "{$class}::{$method} must have at least one parameter");
        $this->assertSame(
            'tenantId',
            $params[0]->getName(),
            "{$class}::{$method} — first parameter must be named 'tenantId'",
        );
        $this->assertTrue(
            $params[0]->hasType(),
            "{$class}::{$method} — first parameter must be type-hinted",
        );
        $this->assertSame(
            'int',
            (string) $params[0]->getType(),
            "{$class}::{$method} — first parameter type must be 'int'",
        );
    }

    // -------------------------------------------------------------------------
    // Interface contract: InventoryStockRepositoryInterface
    // -------------------------------------------------------------------------

    public function testInventoryStockInterfaceTenantScopedMethodsRequireTenantIdFirstParam(): void
    {
        $iface = InventoryStockRepositoryInterface::class;

        foreach (
            [
            'paginateByWarehouse',
            'paginateStockLevelsByWarehouse',
            'locationBelongsToWarehouse',
            'warehouseExists',
            ] as $method
        ) {
            $this->assertFirstParamIsIntTenantId($iface, $method);
        }
    }

    // -------------------------------------------------------------------------
    // Interface contract: StockReservationRepositoryInterface
    // -------------------------------------------------------------------------

    public function testStockReservationInterfaceLookupMethodsRequireTenantIdFirstParam(): void
    {
        $iface = StockReservationRepositoryInterface::class;

        foreach (['findById', 'paginate', 'delete', 'deleteExpired'] as $method) {
            $this->assertFirstParamIsIntTenantId($iface, $method);
        }
    }

    // -------------------------------------------------------------------------
    // Interface contract: TransferOrderRepositoryInterface
    // -------------------------------------------------------------------------

    public function testTransferOrderInterfaceLookupMethodsRequireTenantIdFirstParam(): void
    {
        $iface = TransferOrderRepositoryInterface::class;

        foreach (['findById', 'paginate', 'markAsReceived', 'markAsApproved'] as $method) {
            $this->assertFirstParamIsIntTenantId($iface, $method);
        }
    }

    // -------------------------------------------------------------------------
    // Interface contract: CycleCountRepositoryInterface
    // -------------------------------------------------------------------------

    public function testCycleCountInterfaceLookupMethodsRequireTenantIdFirstParam(): void
    {
        $iface = CycleCountRepositoryInterface::class;

        foreach (['findById', 'paginate', 'markInProgress', 'complete'] as $method) {
            $this->assertFirstParamIsIntTenantId($iface, $method);
        }
    }

    // -------------------------------------------------------------------------
    // Interface contract: CostLayerRepositoryInterface
    // -------------------------------------------------------------------------

    public function testCostLayerInterfaceTenantScopedMethodsRequireTenantIdFirstParam(): void
    {
        $iface = CostLayerRepositoryInterface::class;

        foreach (
            [
            'findOpenLayersOldestFirst',
            'findOpenLayersNewestFirst',
            'findOpenLayersByExpiryAsc',
            'findAllOpenLayers',
            'findById',
            ] as $method
        ) {
            $this->assertFirstParamIsIntTenantId($iface, $method);
        }
    }

    // -------------------------------------------------------------------------
    // Interface contract: ValuationConfigRepositoryInterface
    // -------------------------------------------------------------------------

    public function testValuationConfigInterfaceTenantScopedMethodsRequireTenantIdFirstParam(): void
    {
        $iface = ValuationConfigRepositoryInterface::class;

        foreach (['findById', 'delete', 'resolveEffective', 'paginate'] as $method) {
            $this->assertFirstParamIsIntTenantId($iface, $method);
        }
    }

    // -------------------------------------------------------------------------
    // Implementation: StockReservation and InventoryStock query filters
    // -------------------------------------------------------------------------

    public function testStockReservationAndInventoryStockImplementationsEnforceTenantIdFilter(): void
    {
        $base = __DIR__ . '/../../../app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Repositories/';

        // StockReservation: every public query method applies tenant scope
        $reservationPath = $base . 'EloquentStockReservationRepository.php';
        $this->assertFileExists($reservationPath);
        $reservationSource = (string) file_get_contents($reservationPath);
        $this->assertStringContainsString(
            "->where('tenant_id', \$tenantId)",
            $reservationSource,
            'EloquentStockReservationRepository must filter tenant_id in queries',
        );

        // InventoryStock: paginateByWarehouse and paginateStockLevelsByWarehouse apply tenant scope
        $stockPath = $base . 'EloquentInventoryStockRepository.php';
        $this->assertFileExists($stockPath);
        $stockSource = (string) file_get_contents($stockPath);
        $this->assertStringContainsString(
            "->where('tenant_id', \$tenantId)",
            $stockSource,
            'EloquentInventoryStockRepository must filter tenant_id in warehouse pagination queries',
        );
    }

    // -------------------------------------------------------------------------
    // Implementation: TransferOrder and CycleCount query filters
    // -------------------------------------------------------------------------

    public function testTransferOrderAndCycleCountImplementationsEnforceTenantIdFilter(): void
    {
        $base = __DIR__ . '/../../../app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Repositories/';

        $transferPath = $base . 'EloquentTransferOrderRepository.php';
        $this->assertFileExists($transferPath);
        $transferSource = (string) file_get_contents($transferPath);
        $this->assertStringContainsString(
            "->where('tenant_id', \$tenantId)",
            $transferSource,
            'EloquentTransferOrderRepository must filter tenant_id in queries',
        );

        $cyclePath = $base . 'EloquentCycleCountRepository.php';
        $this->assertFileExists($cyclePath);
        $cycleSource = (string) file_get_contents($cyclePath);
        $this->assertStringContainsString(
            "->where('tenant_id', \$tenantId)",
            $cycleSource,
            'EloquentCycleCountRepository must filter tenant_id in queries',
        );
    }

    // -------------------------------------------------------------------------
    // Critical: update() methods that bypass global tenant scope must guard with tenant_id
    // -------------------------------------------------------------------------

    public function testCostLayerAndValuationConfigUpdateMethodsIncludeTenantIdGuard(): void
    {
        $base = __DIR__ . '/../../../app/Modules/Inventory/Infrastructure/Persistence/Eloquent/Repositories/';

        // CostLayerRepository: update() must scope by tenant_id before id
        $costLayerPath = $base . 'EloquentCostLayerRepository.php';
        $this->assertFileExists($costLayerPath);
        $costLayerSource = (string) file_get_contents($costLayerPath);
        $this->assertStringContainsString(
            'withoutGlobalScope',
            $costLayerSource,
            'EloquentCostLayerRepository::update must call withoutGlobalScope (confirming scope bypass is intentional)',
        );
        $this->assertStringContainsString(
            "->where('tenant_id', \$layer->getTenantId())",
            $costLayerSource,
            'EloquentCostLayerRepository::update must guard with tenant_id before update to prevent cross-tenant write',
        );

        // ValuationConfigRepository: update() must scope by tenant_id before id
        $valuationPath = $base . 'EloquentValuationConfigRepository.php';
        $this->assertFileExists($valuationPath);
        $valuationSource = (string) file_get_contents($valuationPath);
        $this->assertStringContainsString(
            'withoutGlobalScope',
            $valuationSource,
            'EloquentValuationConfigRepository::update must call withoutGlobalScope '
            . '(confirming scope bypass is intentional)',
        );
        $this->assertStringContainsString(
            "->where('tenant_id', \$config->getTenantId())",
            $valuationSource,
            'EloquentValuationConfigRepository::update must guard with tenant_id before update '
            . 'to prevent cross-tenant write',
        );
    }
}
