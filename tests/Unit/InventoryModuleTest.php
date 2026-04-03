<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\CreateInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryBatchData;
use Modules\Inventory\Application\DTOs\InventoryCycleCountData;
use Modules\Inventory\Application\DTOs\InventoryCycleCountLineData;
use Modules\Inventory\Application\DTOs\InventoryLevelData;
use Modules\Inventory\Application\DTOs\InventoryLocationData;
use Modules\Inventory\Application\DTOs\InventorySerialNumberData;
use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Application\DTOs\InventoryValuationLayerData;
use Modules\Inventory\Application\DTOs\UpdateInventoryBatchData;
use Modules\Inventory\Application\DTOs\UpdateInventoryCycleCountData;
use Modules\Inventory\Application\DTOs\UpdateInventoryCycleCountLineData;
use Modules\Inventory\Application\DTOs\UpdateInventoryLocationData;
use Modules\Inventory\Application\DTOs\UpdateInventorySerialNumberData;
use Modules\Inventory\Application\DTOs\UpdateInventorySettingData;
use Modules\Inventory\Application\Services\CreateInventoryBatchService;
use Modules\Inventory\Application\Services\CreateInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\CreateInventoryCycleCountService;
use Modules\Inventory\Application\Services\CreateInventoryLevelService;
use Modules\Inventory\Application\Services\CreateInventoryLocationService;
use Modules\Inventory\Application\Services\CreateInventorySerialNumberService;
use Modules\Inventory\Application\Services\CreateInventorySettingService;
use Modules\Inventory\Application\Services\CreateInventoryValuationLayerService;
use Modules\Inventory\Application\Services\DeleteInventoryBatchService;
use Modules\Inventory\Application\Services\DeleteInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\DeleteInventoryCycleCountService;
use Modules\Inventory\Application\Services\DeleteInventoryLevelService;
use Modules\Inventory\Application\Services\DeleteInventoryLocationService;
use Modules\Inventory\Application\Services\DeleteInventorySerialNumberService;
use Modules\Inventory\Application\Services\DeleteInventorySettingService;
use Modules\Inventory\Application\Services\DeleteInventoryValuationLayerService;
use Modules\Inventory\Application\Services\FindInventoryBatchService;
use Modules\Inventory\Application\Services\FindInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\FindInventoryCycleCountService;
use Modules\Inventory\Application\Services\FindInventoryLevelService;
use Modules\Inventory\Application\Services\FindInventoryLocationService;
use Modules\Inventory\Application\Services\FindInventorySerialNumberService;
use Modules\Inventory\Application\Services\FindInventorySettingService;
use Modules\Inventory\Application\Services\FindInventoryValuationLayerService;
use Modules\Inventory\Application\Services\UpdateInventoryBatchService;
use Modules\Inventory\Application\Services\UpdateInventoryCycleCountLineService;
use Modules\Inventory\Application\Services\UpdateInventoryCycleCountService;
use Modules\Inventory\Application\Services\UpdateInventoryLevelService;
use Modules\Inventory\Application\Services\UpdateInventoryLocationService;
use Modules\Inventory\Application\Services\UpdateInventorySerialNumberService;
use Modules\Inventory\Application\Services\UpdateInventorySettingService;
use Modules\Inventory\Application\Services\UpdateInventoryValuationLayerService;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Entities\InventoryCycleCountLine;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Entities\InventoryLocation;
use Modules\Inventory\Domain\Entities\InventorySerialNumber;
use Modules\Inventory\Domain\Entities\InventorySetting;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\Events\InventoryBatchCreated;
use Modules\Inventory\Domain\Events\InventoryBatchDeleted;
use Modules\Inventory\Domain\Events\InventoryBatchUpdated;
use Modules\Inventory\Domain\Events\InventoryCycleCountCancelled;
use Modules\Inventory\Domain\Events\InventoryCycleCountCreated;
use Modules\Inventory\Domain\Events\InventoryCycleCountLineRecorded;
use Modules\Inventory\Domain\Events\InventoryLevelUpdated;
use Modules\Inventory\Domain\Events\InventoryLocationCreated;
use Modules\Inventory\Domain\Events\InventoryLocationDeleted;
use Modules\Inventory\Domain\Events\InventoryLocationUpdated;
use Modules\Inventory\Domain\Events\InventorySerialNumberCreated;
use Modules\Inventory\Domain\Events\InventorySerialNumberDeleted;
use Modules\Inventory\Domain\Events\InventorySerialNumberUpdated;
use Modules\Inventory\Domain\Events\InventorySettingCreated;
use Modules\Inventory\Domain\Events\InventorySettingDeleted;
use Modules\Inventory\Domain\Events\InventorySettingUpdated;
use Modules\Inventory\Domain\Events\InventoryValuationLayerConsumed;
use Modules\Inventory\Domain\Events\InventoryValuationLayerCreated;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Modules\Inventory\Domain\Exceptions\InventoryBatchNotFoundException;
use Modules\Inventory\Domain\Exceptions\InventoryCycleCountNotFoundException;
use Modules\Inventory\Domain\Exceptions\InventoryLevelNotFoundException;
use Modules\Inventory\Domain\Exceptions\InventoryLocationNotFoundException;
use Modules\Inventory\Domain\Exceptions\InventorySerialNumberNotFoundException;
use Modules\Inventory\Domain\Exceptions\InventorySettingNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountLineRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLocationRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialNumberRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Domain\ValueObjects\CycleCountStatus;
use Modules\Inventory\Domain\ValueObjects\CycleCountMethod;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;
use Modules\Inventory\Domain\ValueObjects\ManagementMethod;
use Modules\Inventory\Domain\ValueObjects\StockRotationStrategy;
use Modules\Inventory\Domain\ValueObjects\LocationType;
use Modules\Inventory\Domain\ValueObjects\SerialStatus;
use Modules\Inventory\Domain\ValueObjects\StockStatus;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryBatchController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryCycleCountController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryCycleCountLineController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryLevelController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryLocationController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventorySerialNumberController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventorySettingController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryValuationLayerController;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryBatchRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryCycleCountLineRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryLevelRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryLocationRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventorySerialNumberRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventorySettingRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryValuationLayerRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryBatchRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryLocationRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventorySerialNumberRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventorySettingRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryBatchCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryBatchResource;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryCycleCountCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryCycleCountResource;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLevelCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLevelResource;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLocationCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLocationResource;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySerialNumberCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySerialNumberResource;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySettingResource;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryValuationLayerCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryValuationLayerResource;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountLineModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLocationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySerialNumberModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySettingModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryValuationLayerModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryBatchRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryCycleCountLineRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryCycleCountRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryLevelRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryLocationRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventorySerialNumberRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventorySettingRepository;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryValuationLayerRepository;
use Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider;
use PHPUnit\Framework\TestCase;

class InventoryModuleTest extends TestCase
{
    // ───────────────────────────── Helpers ─────────────────────────────────

    private function makeSetting(int $id = 1, int $tenantId = 1): InventorySetting
    {
        $s = new InventorySetting(tenantId: $tenantId);
        $r = new \ReflectionProperty($s, 'id');
        $r->setAccessible(true);
        $r->setValue($s, $id);

        return $s;
    }

    private function makeLocation(int $id = 1, int $tenantId = 1, int $warehouseId = 10): InventoryLocation
    {
        $l = new InventoryLocation(tenantId: $tenantId, warehouseId: $warehouseId, name: 'Bin A1', type: 'bin');
        $r = new \ReflectionProperty($l, 'id');
        $r->setAccessible(true);
        $r->setValue($l, $id);

        return $l;
    }

    private function makeBatch(int $id = 1, int $tenantId = 1, int $productId = 5): InventoryBatch
    {
        $b = new InventoryBatch(tenantId: $tenantId, productId: $productId, batchNumber: 'BATCH-001');
        $r = new \ReflectionProperty($b, 'id');
        $r->setAccessible(true);
        $r->setValue($b, $id);

        return $b;
    }

    private function makeSerial(int $id = 1, int $tenantId = 1, int $productId = 5): InventorySerialNumber
    {
        $s = new InventorySerialNumber(tenantId: $tenantId, productId: $productId, serialNumber: 'SN-001');
        $r = new \ReflectionProperty($s, 'id');
        $r->setAccessible(true);
        $r->setValue($s, $id);

        return $s;
    }

    private function makeLevel(int $id = 1, int $tenantId = 1, int $productId = 5): InventoryLevel
    {
        $l = new InventoryLevel(tenantId: $tenantId, productId: $productId, qtyOnHand: 100.0);
        $r = new \ReflectionProperty($l, 'id');
        $r->setAccessible(true);
        $r->setValue($l, $id);

        return $l;
    }

    private function makeValuationLayer(int $id = 1, int $tenantId = 1, int $productId = 5): InventoryValuationLayer
    {
        $v = new InventoryValuationLayer(
            tenantId: $tenantId, productId: $productId,
            layerDate: new \DateTimeImmutable('2026-01-01'), qtyIn: 50.0,
            unitCost: 10.0, valuationMethod: 'fifo',
        );
        $r = new \ReflectionProperty($v, 'id');
        $r->setAccessible(true);
        $r->setValue($v, $id);

        return $v;
    }

    private function makeCycleCount(int $id = 1, int $tenantId = 1): InventoryCycleCount
    {
        $c = new InventoryCycleCount(tenantId: $tenantId, referenceNumber: 'CC-001', warehouseId: 10);
        $r = new \ReflectionProperty($c, 'id');
        $r->setAccessible(true);
        $r->setValue($c, $id);

        return $c;
    }

    private function makeCycleCountLine(int $id = 1, int $tenantId = 1, int $cycleCountId = 1): InventoryCycleCountLine
    {
        $l = new InventoryCycleCountLine(tenantId: $tenantId, cycleCountId: $cycleCountId, productId: 5, expectedQty: 10.0);
        $r = new \ReflectionProperty($l, 'id');
        $r->setAccessible(true);
        $r->setValue($l, $id);

        return $l;
    }

    // ──────────────────────────── Value Objects ─────────────────────────────

    /** @test */
    public function test_valuation_method_valid_values(): void
    {
        $this->assertContains('fifo', ValuationMethod::values());
        $this->assertContains('lifo', ValuationMethod::values());
        $this->assertContains('avco', ValuationMethod::values());
        $this->assertContains('standard_cost', ValuationMethod::values());
        $this->assertContains('specific_identification', ValuationMethod::values());
    }

    /** @test */
    public function test_valuation_method_fifo_constant(): void
    {
        $this->assertSame('fifo', ValuationMethod::FIFO);
    }

    /** @test */
    public function test_stock_status_values(): void
    {
        $this->assertContains('available', StockStatus::values());
        $this->assertContains('reserved', StockStatus::values());
    }

    /** @test */
    public function test_serial_status_values(): void
    {
        $this->assertContains('available', SerialStatus::values());
        $this->assertContains('sold', SerialStatus::values());
    }

    /** @test */
    public function test_location_type_values(): void
    {
        $this->assertContains('bin', LocationType::values());
        $this->assertContains('rack', LocationType::values());
    }

    /** @test */
    public function test_cycle_count_status_values(): void
    {
        $this->assertContains('draft', CycleCountStatus::values());
        $this->assertContains('completed', CycleCountStatus::values());
    }

    // ── ManagementMethod Value Object ─────────────────────────────────────────

    /** @test */
    public function test_management_method_valid_values(): void
    {
        $this->assertContains('perpetual', ManagementMethod::values());
        $this->assertContains('periodic', ManagementMethod::values());
    }

    /** @test */
    public function test_management_method_constants(): void
    {
        $this->assertSame('perpetual', ManagementMethod::PERPETUAL);
        $this->assertSame('periodic',  ManagementMethod::PERIODIC);
    }

    /** @test */
    public function test_management_method_is_perpetual(): void
    {
        $m = new ManagementMethod('perpetual');
        $this->assertTrue($m->isPerpetual());
        $this->assertFalse($m->isPeriodic());
    }

    /** @test */
    public function test_management_method_is_periodic(): void
    {
        $m = new ManagementMethod('periodic');
        $this->assertTrue($m->isPeriodic());
        $this->assertFalse($m->isPerpetual());
    }

    /** @test */
    public function test_management_method_value(): void
    {
        $m = new ManagementMethod('perpetual');
        $this->assertSame('perpetual', $m->value());
        $this->assertSame('perpetual', (string) $m);
    }

    /** @test */
    public function test_management_method_equals(): void
    {
        $a = new ManagementMethod('perpetual');
        $b = new ManagementMethod('perpetual');
        $c = new ManagementMethod('periodic');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    /** @test */
    public function test_management_method_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ManagementMethod('invalid_method');
    }

    /** @test */
    public function test_management_method_assert_valid_passes_for_valid_value(): void
    {
        ManagementMethod::assertValid('perpetual'); // must not throw
        $this->assertTrue(true);
    }

    /** @test */
    public function test_management_method_assert_valid_throws_for_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ManagementMethod::assertValid('bad_value');
    }

    // ── StockRotationStrategy Value Object ────────────────────────────────────

    /** @test */
    public function test_stock_rotation_strategy_valid_values(): void
    {
        $values = StockRotationStrategy::values();
        $this->assertContains('fifo', $values);
        $this->assertContains('fefo', $values);
        $this->assertContains('lifo', $values);
        $this->assertContains('fmfo', $values);
    }

    /** @test */
    public function test_stock_rotation_strategy_constants(): void
    {
        $this->assertSame('fifo', StockRotationStrategy::FIFO);
        $this->assertSame('fefo', StockRotationStrategy::FEFO);
        $this->assertSame('lifo', StockRotationStrategy::LIFO);
        $this->assertSame('fmfo', StockRotationStrategy::FMFO);
    }

    /** @test */
    public function test_stock_rotation_strategy_fifo(): void
    {
        $s = new StockRotationStrategy('fifo');
        $this->assertTrue($s->isFifo());
        $this->assertFalse($s->isFefo());
        $this->assertFalse($s->isLifo());
        $this->assertFalse($s->isFmfo());
    }

    /** @test */
    public function test_stock_rotation_strategy_fefo(): void
    {
        $s = new StockRotationStrategy('fefo');
        $this->assertTrue($s->isFefo());
        $this->assertFalse($s->isFifo());
    }

    /** @test */
    public function test_stock_rotation_strategy_lifo(): void
    {
        $s = new StockRotationStrategy('lifo');
        $this->assertTrue($s->isLifo());
    }

    /** @test */
    public function test_stock_rotation_strategy_fmfo(): void
    {
        $s = new StockRotationStrategy('fmfo');
        $this->assertTrue($s->isFmfo());
    }

    /** @test */
    public function test_stock_rotation_strategy_value_and_to_string(): void
    {
        $s = new StockRotationStrategy('fefo');
        $this->assertSame('fefo', $s->value());
        $this->assertSame('fefo', (string) $s);
    }

    /** @test */
    public function test_stock_rotation_strategy_equals(): void
    {
        $a = new StockRotationStrategy('fefo');
        $b = new StockRotationStrategy('fefo');
        $c = new StockRotationStrategy('fifo');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    /** @test */
    public function test_stock_rotation_strategy_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StockRotationStrategy('bad_strategy');
    }

    /** @test */
    public function test_stock_rotation_strategy_assert_valid_passes_for_valid_value(): void
    {
        StockRotationStrategy::assertValid('fefo'); // must not throw
        $this->assertTrue(true);
    }

    /** @test */
    public function test_stock_rotation_strategy_assert_valid_throws_for_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StockRotationStrategy::assertValid('bad_strategy');
    }

    // ── AllocationAlgorithm Value Object ──────────────────────────────────────

    /** @test */
    public function test_allocation_algorithm_valid_values(): void
    {
        $values = AllocationAlgorithm::values();
        $this->assertContains('fefo',        $values);
        $this->assertContains('fifo',        $values);
        $this->assertContains('lifo',        $values);
        $this->assertContains('zone_based',  $values);
        $this->assertContains('demand_based', $values);
    }

    /** @test */
    public function test_allocation_algorithm_constants(): void
    {
        $this->assertSame('fefo',         AllocationAlgorithm::FEFO);
        $this->assertSame('fifo',         AllocationAlgorithm::FIFO);
        $this->assertSame('lifo',         AllocationAlgorithm::LIFO);
        $this->assertSame('zone_based',   AllocationAlgorithm::ZONE_BASED);
        $this->assertSame('demand_based', AllocationAlgorithm::DEMAND_BASED);
    }

    /** @test */
    public function test_allocation_algorithm_fefo(): void
    {
        $a = new AllocationAlgorithm('fefo');
        $this->assertTrue($a->isFefo());
        $this->assertFalse($a->isFifo());
        $this->assertFalse($a->isLifo());
        $this->assertFalse($a->isZoneBased());
        $this->assertFalse($a->isDemandBased());
    }

    /** @test */
    public function test_allocation_algorithm_fifo(): void
    {
        $a = new AllocationAlgorithm('fifo');
        $this->assertTrue($a->isFifo());
    }

    /** @test */
    public function test_allocation_algorithm_zone_based(): void
    {
        $a = new AllocationAlgorithm('zone_based');
        $this->assertTrue($a->isZoneBased());
    }

    /** @test */
    public function test_allocation_algorithm_demand_based(): void
    {
        $a = new AllocationAlgorithm('demand_based');
        $this->assertTrue($a->isDemandBased());
    }

    /** @test */
    public function test_allocation_algorithm_value_and_to_string(): void
    {
        $a = new AllocationAlgorithm('fifo');
        $this->assertSame('fifo', $a->value());
        $this->assertSame('fifo', (string) $a);
    }

    /** @test */
    public function test_allocation_algorithm_equals(): void
    {
        $a = new AllocationAlgorithm('fefo');
        $b = new AllocationAlgorithm('fefo');
        $c = new AllocationAlgorithm('fifo');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    /** @test */
    public function test_allocation_algorithm_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AllocationAlgorithm('invalid_algo');
    }

    /** @test */
    public function test_allocation_algorithm_assert_valid_passes_for_valid_value(): void
    {
        AllocationAlgorithm::assertValid('zone_based'); // must not throw
        $this->assertTrue(true);
    }

    /** @test */
    public function test_allocation_algorithm_assert_valid_throws_for_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AllocationAlgorithm::assertValid('invalid_algo');
    }

    // ── CycleCountMethod Value Object ─────────────────────────────────────────

    /** @test */
    public function test_cycle_count_method_valid_values(): void
    {
        $values = CycleCountMethod::values();
        $this->assertContains('abc',       $values);
        $this->assertContains('frequency', $values);
        $this->assertContains('random',    $values);
        $this->assertContains('periodic',  $values);
    }

    /** @test */
    public function test_cycle_count_method_constants(): void
    {
        $this->assertSame('abc',       CycleCountMethod::ABC);
        $this->assertSame('frequency', CycleCountMethod::FREQUENCY);
        $this->assertSame('random',    CycleCountMethod::RANDOM);
        $this->assertSame('periodic',  CycleCountMethod::PERIODIC);
    }

    /** @test */
    public function test_cycle_count_method_abc(): void
    {
        $m = new CycleCountMethod('abc');
        $this->assertTrue($m->isAbc());
        $this->assertFalse($m->isFrequency());
        $this->assertFalse($m->isRandom());
        $this->assertFalse($m->isPeriodic());
    }

    /** @test */
    public function test_cycle_count_method_frequency(): void
    {
        $m = new CycleCountMethod('frequency');
        $this->assertTrue($m->isFrequency());
    }

    /** @test */
    public function test_cycle_count_method_random(): void
    {
        $m = new CycleCountMethod('random');
        $this->assertTrue($m->isRandom());
    }

    /** @test */
    public function test_cycle_count_method_periodic(): void
    {
        $m = new CycleCountMethod('periodic');
        $this->assertTrue($m->isPeriodic());
    }

    /** @test */
    public function test_cycle_count_method_value_and_to_string(): void
    {
        $m = new CycleCountMethod('abc');
        $this->assertSame('abc', $m->value());
        $this->assertSame('abc', (string) $m);
    }

    /** @test */
    public function test_cycle_count_method_equals(): void
    {
        $a = new CycleCountMethod('abc');
        $b = new CycleCountMethod('abc');
        $c = new CycleCountMethod('random');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    /** @test */
    public function test_cycle_count_method_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CycleCountMethod('invalid_method');
    }

    /** @test */
    public function test_cycle_count_method_assert_valid_passes_for_valid_value(): void
    {
        CycleCountMethod::assertValid('abc'); // must not throw
        $this->assertTrue(true);
    }

    /** @test */
    public function test_cycle_count_method_assert_valid_throws_for_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CycleCountMethod::assertValid('invalid_method');
    }

    // ── InventorySetting validates via VOs ────────────────────────────────────

    /** @test */
    public function test_inventory_setting_rejects_invalid_management_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new InventorySetting(tenantId: 1, managementMethod: 'invalid');
    }

    /** @test */
    public function test_inventory_setting_rejects_invalid_rotation_strategy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new InventorySetting(tenantId: 1, rotationStrategy: 'invalid');
    }

    /** @test */
    public function test_inventory_setting_rejects_invalid_allocation_algorithm(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new InventorySetting(tenantId: 1, allocationAlgorithm: 'invalid');
    }

    /** @test */
    public function test_inventory_setting_rejects_invalid_cycle_count_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new InventorySetting(tenantId: 1, cycleCountMethod: 'invalid');
    }

    /** @test */
    public function test_inventory_setting_update_rejects_invalid_management_method(): void
    {
        $s = $this->makeSetting();
        $this->expectException(\InvalidArgumentException::class);
        $s->updateDetails('fifo', 'invalid', 'fefo', 'fefo', 'abc', false, true, true, true, false, true, null, true);
    }

    /** @test */
    public function test_inventory_setting_update_rejects_invalid_rotation_strategy(): void
    {
        $s = $this->makeSetting();
        $this->expectException(\InvalidArgumentException::class);
        $s->updateDetails('fifo', 'perpetual', 'invalid', 'fefo', 'abc', false, true, true, true, false, true, null, true);
    }

    /** @test */
    public function test_inventory_setting_update_rejects_invalid_allocation_algorithm(): void
    {
        $s = $this->makeSetting();
        $this->expectException(\InvalidArgumentException::class);
        $s->updateDetails('fifo', 'perpetual', 'fefo', 'invalid', 'abc', false, true, true, true, false, true, null, true);
    }

    /** @test */
    public function test_inventory_setting_update_rejects_invalid_cycle_count_method(): void
    {
        $s = $this->makeSetting();
        $this->expectException(\InvalidArgumentException::class);
        $s->updateDetails('fifo', 'perpetual', 'fefo', 'fefo', 'invalid', false, true, true, true, false, true, null, true);
    }

    // ──────────────────────────── Domain Entities ────────────────────────────

    /** @test */
    public function test_inventory_setting_entity_defaults(): void
    {
        $s = new InventorySetting(tenantId: 1);
        $this->assertSame(1, $s->getTenantId());
        $this->assertSame('fifo', $s->getValuationMethod());
        $this->assertSame('perpetual', $s->getManagementMethod());
        $this->assertSame('fefo', $s->getRotationStrategy());
        $this->assertFalse($s->isNegativeStockAllowed());
        $this->assertTrue($s->isTrackLots());
        $this->assertTrue($s->isActive());
        $this->assertNull($s->getId());
    }

    /** @test */
    public function test_inventory_setting_update_details(): void
    {
        $s = $this->makeSetting();
        $s->updateDetails('lifo', 'periodic', 'lifo', 'fifo', 'frequency', true, false, false, false, true, true, null, false);
        $this->assertSame('lifo', $s->getValuationMethod());
        $this->assertSame('periodic', $s->getManagementMethod());
        $this->assertTrue($s->isNegativeStockAllowed());
        $this->assertFalse($s->isActive());
    }

    /** @test */
    public function test_inventory_location_entity(): void
    {
        $l = new InventoryLocation(tenantId: 1, warehouseId: 10, name: 'Shelf B2', type: 'shelf');
        $this->assertSame(1, $l->getTenantId());
        $this->assertSame(10, $l->getWarehouseId());
        $this->assertSame('Shelf B2', $l->getName());
        $this->assertSame('shelf', $l->getType());
        $this->assertTrue($l->isPickable());
        $this->assertTrue($l->isStorable());
        $this->assertFalse($l->isPacking());
        $this->assertTrue($l->isActive());
    }

    /** @test */
    public function test_inventory_location_activate_deactivate(): void
    {
        $l = $this->makeLocation();
        $l->deactivate();
        $this->assertFalse($l->isActive());
        $l->activate();
        $this->assertTrue($l->isActive());
    }

    /** @test */
    public function test_inventory_batch_entity(): void
    {
        $b = new InventoryBatch(tenantId: 1, productId: 5, batchNumber: 'BATCH-001', initialQty: 100.0, unitCost: 5.0);
        $this->assertSame(1, $b->getTenantId());
        $this->assertSame(5, $b->getProductId());
        $this->assertSame('BATCH-001', $b->getBatchNumber());
        $this->assertEqualsWithDelta(100.0, $b->getInitialQty(), 0.001);
        $this->assertSame('active', $b->getStatus());
    }

    /** @test */
    public function test_inventory_batch_consume_stock(): void
    {
        $b = new InventoryBatch(tenantId: 1, productId: 5, batchNumber: 'BATCH-002', initialQty: 50.0, remainingQty: 50.0);
        $b->consume(20.0);
        $this->assertEqualsWithDelta(30.0, $b->getRemainingQty(), 0.001);
    }

    /** @test */
    public function test_inventory_batch_quarantine(): void
    {
        $b = $this->makeBatch();
        $b->quarantine();
        $this->assertSame('quarantine', $b->getStatus());
    }

    /** @test */
    public function test_inventory_batch_is_expired(): void
    {
        $past = new \DateTimeImmutable('2020-01-01');
        $b    = new InventoryBatch(tenantId: 1, productId: 5, batchNumber: 'B', expiryDate: $past);
        $this->assertTrue($b->isExpired());
    }

    /** @test */
    public function test_inventory_serial_number_entity(): void
    {
        $s = new InventorySerialNumber(tenantId: 1, productId: 5, serialNumber: 'SN-XYZ');
        $this->assertSame('SN-XYZ', $s->getSerialNumber());
        $this->assertSame('available', $s->getStatus());
    }

    /** @test */
    public function test_inventory_serial_number_mark_sold(): void
    {
        $s = $this->makeSerial();
        $s->markSold();
        $this->assertSame('sold', $s->getStatus());
        $this->assertNotNull($s->getSoldAt());
    }

    /** @test */
    public function test_inventory_serial_number_mark_returned(): void
    {
        $s = $this->makeSerial();
        $s->markSold();
        $s->markReturned();
        $this->assertSame('returned', $s->getStatus());
    }

    /** @test */
    public function test_inventory_level_add_stock(): void
    {
        $l = $this->makeLevel();
        $before = $l->getQtyOnHand();
        $l->addStock(25.0);
        $this->assertEqualsWithDelta($before + 25.0, $l->getQtyOnHand(), 0.001);
    }

    /** @test */
    public function test_inventory_level_remove_stock(): void
    {
        $l = $this->makeLevel();
        $l->removeStock(30.0);
        $this->assertEqualsWithDelta(70.0, $l->getQtyOnHand(), 0.001);
    }

    /** @test */
    public function test_inventory_level_insufficient_stock_throws(): void
    {
        $l = $this->makeLevel();
        $this->expectException(InsufficientStockException::class);
        $l->removeStock(200.0);
    }

    /** @test */
    public function test_inventory_level_reserve_and_release(): void
    {
        $l = $this->makeLevel();
        $l->reserve(10.0);
        $this->assertEqualsWithDelta(10.0, $l->getQtyReserved(), 0.001);
        $l->release(5.0);
        $this->assertEqualsWithDelta(5.0, $l->getQtyReserved(), 0.001);
    }

    /** @test */
    public function test_inventory_level_is_low_stock(): void
    {
        $l = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 5.0, reorderPoint: 10.0);
        $this->assertTrue($l->isLowStock());
    }

    /** @test */
    public function test_inventory_valuation_layer_consume(): void
    {
        $v = $this->makeValuationLayer();
        $consumed = $v->consume(30.0);
        $this->assertEqualsWithDelta(30.0, $consumed, 0.001);
        $this->assertEqualsWithDelta(20.0, $v->getQtyRemaining(), 0.001);
        $this->assertFalse($v->isClosed());
    }

    /** @test */
    public function test_inventory_valuation_layer_fully_consumed_closes(): void
    {
        $v = $this->makeValuationLayer();
        $v->consume(50.0);
        $this->assertTrue($v->isClosed());
        $this->assertEqualsWithDelta(0.0, $v->getQtyRemaining(), 0.001);
    }

    /** @test */
    public function test_inventory_valuation_layer_total_value(): void
    {
        $v = $this->makeValuationLayer();
        $this->assertEqualsWithDelta(500.0, $v->getTotalValue(), 0.001);
    }

    /** @test */
    public function test_inventory_cycle_count_lifecycle(): void
    {
        $c = $this->makeCycleCount();
        $this->assertSame('draft', $c->getStatus());
        $c->start();
        $this->assertSame('in_progress', $c->getStatus());
        $c->complete();
        $this->assertSame('completed', $c->getStatus());
    }

    /** @test */
    public function test_inventory_cycle_count_cancel(): void
    {
        $c = $this->makeCycleCount();
        $c->cancel();
        $this->assertSame('cancelled', $c->getStatus());
    }

    /** @test */
    public function test_inventory_cycle_count_line_record_count(): void
    {
        $l = $this->makeCycleCountLine();
        $l->recordCount(8.0, 42);
        $this->assertEqualsWithDelta(8.0, $l->getCountedQty(), 0.001);
        $this->assertEqualsWithDelta(-2.0, $l->getVarianceQty(), 0.001);
        $this->assertSame(42, $l->getCountedBy());
    }

    /** @test */
    public function test_inventory_cycle_count_line_approve_reject(): void
    {
        $l = $this->makeCycleCountLine();
        $l->recordCount(10.0);
        $l->approve();
        $this->assertSame('approved', $l->getStatus());

        $l2 = $this->makeCycleCountLine();
        $l2->recordCount(5.0);
        $l2->reject();
        $this->assertSame('rejected', $l2->getStatus());
    }

    // ──────────────────────────── Domain Events ──────────────────────────────

    /** @test */
    public function test_inventory_setting_events_exist(): void
    {
        $s = $this->makeSetting();
        $this->assertInstanceOf(InventorySettingCreated::class, new InventorySettingCreated($s));
        $this->assertInstanceOf(InventorySettingUpdated::class, new InventorySettingUpdated($s));
        $this->assertInstanceOf(InventorySettingDeleted::class, new InventorySettingDeleted($s));
    }

    /** @test */
    public function test_inventory_location_events_exist(): void
    {
        $l = $this->makeLocation();
        $this->assertInstanceOf(InventoryLocationCreated::class, new InventoryLocationCreated($l));
        $this->assertInstanceOf(InventoryLocationUpdated::class, new InventoryLocationUpdated($l));
        $this->assertInstanceOf(InventoryLocationDeleted::class, new InventoryLocationDeleted($l));
    }

    /** @test */
    public function test_inventory_batch_events_exist(): void
    {
        $b = $this->makeBatch();
        $this->assertInstanceOf(InventoryBatchCreated::class, new InventoryBatchCreated($b));
        $this->assertInstanceOf(InventoryBatchUpdated::class, new InventoryBatchUpdated($b));
        $this->assertInstanceOf(InventoryBatchDeleted::class, new InventoryBatchDeleted($b));
    }

    /** @test */
    public function test_inventory_serial_number_events_exist(): void
    {
        $s = $this->makeSerial();
        $this->assertInstanceOf(InventorySerialNumberCreated::class, new InventorySerialNumberCreated($s));
        $this->assertInstanceOf(InventorySerialNumberUpdated::class, new InventorySerialNumberUpdated($s));
        $this->assertInstanceOf(InventorySerialNumberDeleted::class, new InventorySerialNumberDeleted($s));
    }

    /** @test */
    public function test_inventory_valuation_layer_events_exist(): void
    {
        $v = $this->makeValuationLayer();
        $this->assertInstanceOf(InventoryValuationLayerCreated::class, new InventoryValuationLayerCreated($v));
        $this->assertInstanceOf(InventoryValuationLayerConsumed::class, new InventoryValuationLayerConsumed($v));
    }

    /** @test */
    public function test_inventory_level_event_exists(): void
    {
        $l = $this->makeLevel();
        $this->assertInstanceOf(InventoryLevelUpdated::class, new InventoryLevelUpdated($l));
    }

    /** @test */
    public function test_inventory_cycle_count_events_exist(): void
    {
        $c = $this->makeCycleCount();
        $this->assertInstanceOf(InventoryCycleCountCreated::class, new InventoryCycleCountCreated($c));
        $this->assertInstanceOf(InventoryCycleCountCancelled::class, new InventoryCycleCountCancelled($c));
    }

    /** @test */
    public function test_inventory_cycle_count_line_event_exists(): void
    {
        $l = $this->makeCycleCountLine();
        $this->assertInstanceOf(InventoryCycleCountLineRecorded::class, new InventoryCycleCountLineRecorded($l));
    }

    // ──────────────────────────── Exceptions ─────────────────────────────────

    /** @test */
    public function test_inventory_exceptions_extend_not_found(): void
    {
        $this->assertInstanceOf(\Modules\Core\Domain\Exceptions\NotFoundException::class, new InventorySettingNotFoundException(1));
        $this->assertInstanceOf(\Modules\Core\Domain\Exceptions\NotFoundException::class, new InventoryLocationNotFoundException(1));
        $this->assertInstanceOf(\Modules\Core\Domain\Exceptions\NotFoundException::class, new InventoryBatchNotFoundException(1));
        $this->assertInstanceOf(\Modules\Core\Domain\Exceptions\NotFoundException::class, new InventorySerialNumberNotFoundException(1));
        $this->assertInstanceOf(\Modules\Core\Domain\Exceptions\NotFoundException::class, new InventoryLevelNotFoundException(1));
        $this->assertInstanceOf(\Modules\Core\Domain\Exceptions\NotFoundException::class, new InventoryCycleCountNotFoundException(1));
    }

    /** @test */
    public function test_insufficient_stock_exception(): void
    {
        $e = new InsufficientStockException(5, 100.0, 30.0);
        $this->assertStringContainsString('5', $e->getMessage());
    }

    // ──────────────────────────── DTOs ───────────────────────────────────────

    /** @test */
    public function test_inventory_setting_data_dto_extends_base(): void
    {
        $this->assertInstanceOf(BaseDto::class, new InventorySettingData());
    }

    /** @test */
    public function test_inventory_location_data_dto_from_array(): void
    {
        $dto = InventoryLocationData::fromArray([
            'tenantId'    => 1,
            'warehouseId' => 10,
            'name'        => 'Row A',
            'type'        => 'rack',
        ]);
        $this->assertSame(1, $dto->tenantId);
        $this->assertSame(10, $dto->warehouseId);
        $this->assertSame('Row A', $dto->name);
        $this->assertSame('rack', $dto->type);
    }

    /** @test */
    public function test_inventory_batch_data_dto_from_array(): void
    {
        $dto = InventoryBatchData::fromArray([
            'tenantId'    => 1,
            'productId'   => 5,
            'batchNumber' => 'BN-007',
            'initialQty'  => 200.0,
            'unitCost'    => 3.50,
        ]);
        $this->assertSame('BN-007', $dto->batchNumber);
        $this->assertEqualsWithDelta(200.0, $dto->initialQty, 0.001);
    }

    /** @test */
    public function test_update_inventory_setting_data_dto(): void
    {
        $dto = UpdateInventorySettingData::fromArray(['id' => 1, 'valuationMethod' => 'avco']);
        $this->assertSame(1, $dto->id);
        $this->assertSame('avco', $dto->valuationMethod);
    }

    // ──────────────────────────── Repository Interfaces ──────────────────────

    /** @test */
    public function test_all_inventory_repository_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(InventorySettingRepositoryInterface::class));
        $this->assertTrue(interface_exists(InventoryLocationRepositoryInterface::class));
        $this->assertTrue(interface_exists(InventoryBatchRepositoryInterface::class));
        $this->assertTrue(interface_exists(InventorySerialNumberRepositoryInterface::class));
        $this->assertTrue(interface_exists(InventoryLevelRepositoryInterface::class));
        $this->assertTrue(interface_exists(InventoryValuationLayerRepositoryInterface::class));
        $this->assertTrue(interface_exists(InventoryCycleCountRepositoryInterface::class));
        $this->assertTrue(interface_exists(InventoryCycleCountLineRepositoryInterface::class));
    }

    // ──────────────────────────── Service Contracts ──────────────────────────

    /** @test */
    public function test_all_write_service_interfaces_extend_write_service_interface(): void
    {
        $writeInterfaces = [
            CreateInventorySettingServiceInterface::class,
            UpdateInventorySettingServiceInterface::class,
            DeleteInventorySettingServiceInterface::class,
            CreateInventoryLocationServiceInterface::class,
            UpdateInventoryLocationServiceInterface::class,
            DeleteInventoryLocationServiceInterface::class,
            CreateInventoryBatchServiceInterface::class,
            UpdateInventoryBatchServiceInterface::class,
            DeleteInventoryBatchServiceInterface::class,
            CreateInventorySerialNumberServiceInterface::class,
            UpdateInventorySerialNumberServiceInterface::class,
            DeleteInventorySerialNumberServiceInterface::class,
            CreateInventoryLevelServiceInterface::class,
            UpdateInventoryLevelServiceInterface::class,
            DeleteInventoryLevelServiceInterface::class,
            CreateInventoryValuationLayerServiceInterface::class,
            UpdateInventoryValuationLayerServiceInterface::class,
            DeleteInventoryValuationLayerServiceInterface::class,
            CreateInventoryCycleCountServiceInterface::class,
            UpdateInventoryCycleCountServiceInterface::class,
            DeleteInventoryCycleCountServiceInterface::class,
            CreateInventoryCycleCountLineServiceInterface::class,
            UpdateInventoryCycleCountLineServiceInterface::class,
            DeleteInventoryCycleCountLineServiceInterface::class,
        ];

        foreach ($writeInterfaces as $iface) {
            $this->assertTrue(
                interface_exists($iface),
                "Interface {$iface} does not exist."
            );
            $this->assertTrue(
                is_a($iface, WriteServiceInterface::class, true),
                "Interface {$iface} does not extend WriteServiceInterface."
            );
        }
    }

    /** @test */
    public function test_all_find_service_interfaces_extend_read_service_interface(): void
    {
        $readInterfaces = [
            FindInventorySettingServiceInterface::class,
            FindInventoryLocationServiceInterface::class,
            FindInventoryBatchServiceInterface::class,
            FindInventorySerialNumberServiceInterface::class,
            FindInventoryLevelServiceInterface::class,
            FindInventoryValuationLayerServiceInterface::class,
            FindInventoryCycleCountServiceInterface::class,
            FindInventoryCycleCountLineServiceInterface::class,
        ];

        foreach ($readInterfaces as $iface) {
            $this->assertTrue(interface_exists($iface), "Interface {$iface} does not exist.");
            $this->assertTrue(is_a($iface, ReadServiceInterface::class, true));
        }
    }

    // ──────────────────────────── Services ───────────────────────────────────

    /** @test */
    public function test_create_inventory_setting_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(CreateInventorySettingService::class, BaseService::class));
        $this->assertTrue(is_a(CreateInventorySettingService::class, CreateInventorySettingServiceInterface::class, true));
    }

    /** @test */
    public function test_find_inventory_setting_service_implements_interface(): void
    {
        $this->assertTrue(is_a(FindInventorySettingService::class, FindInventorySettingServiceInterface::class, true));
    }

    /** @test */
    public function test_create_inventory_location_service_implements_interface(): void
    {
        $this->assertTrue(is_a(CreateInventoryLocationService::class, CreateInventoryLocationServiceInterface::class, true));
    }

    /** @test */
    public function test_create_inventory_batch_service_implements_interface(): void
    {
        $this->assertTrue(is_a(CreateInventoryBatchService::class, CreateInventoryBatchServiceInterface::class, true));
    }

    /** @test */
    public function test_find_inventory_batch_service_implements_interface(): void
    {
        $this->assertTrue(is_a(FindInventoryBatchService::class, FindInventoryBatchServiceInterface::class, true));
    }

    /** @test */
    public function test_create_serial_number_service_implements_interface(): void
    {
        $this->assertTrue(is_a(CreateInventorySerialNumberService::class, CreateInventorySerialNumberServiceInterface::class, true));
    }

    /** @test */
    public function test_create_inventory_level_service_implements_interface(): void
    {
        $this->assertTrue(is_a(CreateInventoryLevelService::class, CreateInventoryLevelServiceInterface::class, true));
    }

    /** @test */
    public function test_find_inventory_level_service_implements_interface(): void
    {
        $this->assertTrue(is_a(FindInventoryLevelService::class, FindInventoryLevelServiceInterface::class, true));
    }

    /** @test */
    public function test_create_valuation_layer_service_implements_interface(): void
    {
        $this->assertTrue(is_a(CreateInventoryValuationLayerService::class, CreateInventoryValuationLayerServiceInterface::class, true));
    }

    /** @test */
    public function test_create_cycle_count_service_implements_interface(): void
    {
        $this->assertTrue(is_a(CreateInventoryCycleCountService::class, CreateInventoryCycleCountServiceInterface::class, true));
    }

    /** @test */
    public function test_find_cycle_count_service_implements_interface(): void
    {
        $this->assertTrue(is_a(FindInventoryCycleCountService::class, FindInventoryCycleCountServiceInterface::class, true));
    }

    /** @test */
    public function test_create_cycle_count_line_service_implements_interface(): void
    {
        $this->assertTrue(is_a(CreateInventoryCycleCountLineService::class, CreateInventoryCycleCountLineServiceInterface::class, true));
    }

    // ──────────────────────────── Eloquent Models ─────────────────────────────

    /** @test */
    public function test_inventory_setting_model_has_correct_table(): void
    {
        $m = new InventorySettingModel();
        $this->assertSame('inventory_settings', $m->getTable());
    }

    /** @test */
    public function test_inventory_location_model_has_correct_table(): void
    {
        $m = new InventoryLocationModel();
        $this->assertSame('inventory_locations', $m->getTable());
    }

    /** @test */
    public function test_inventory_batch_model_has_correct_table(): void
    {
        $m = new InventoryBatchModel();
        $this->assertSame('inventory_batches', $m->getTable());
    }

    /** @test */
    public function test_inventory_serial_number_model_has_correct_table(): void
    {
        $m = new InventorySerialNumberModel();
        $this->assertSame('inventory_serial_numbers', $m->getTable());
    }

    /** @test */
    public function test_inventory_level_model_has_correct_table(): void
    {
        $m = new InventoryLevelModel();
        $this->assertSame('inventory_levels', $m->getTable());
    }

    /** @test */
    public function test_inventory_valuation_layer_model_has_correct_table(): void
    {
        $m = new InventoryValuationLayerModel();
        $this->assertSame('inventory_valuation_layers', $m->getTable());
    }

    /** @test */
    public function test_inventory_cycle_count_model_has_correct_table(): void
    {
        $m = new InventoryCycleCountModel();
        $this->assertSame('inventory_cycle_counts', $m->getTable());
    }

    /** @test */
    public function test_inventory_cycle_count_line_model_has_correct_table(): void
    {
        $m = new InventoryCycleCountLineModel();
        $this->assertSame('inventory_cycle_count_lines', $m->getTable());
    }

    // ──────────────────────────── Repositories ────────────────────────────────

    /** @test */
    public function test_inventory_repositories_implement_interfaces(): void
    {
        $this->assertTrue(is_a(EloquentInventorySettingRepository::class, InventorySettingRepositoryInterface::class, true));
        $this->assertTrue(is_a(EloquentInventoryLocationRepository::class, InventoryLocationRepositoryInterface::class, true));
        $this->assertTrue(is_a(EloquentInventoryBatchRepository::class, InventoryBatchRepositoryInterface::class, true));
        $this->assertTrue(is_a(EloquentInventorySerialNumberRepository::class, InventorySerialNumberRepositoryInterface::class, true));
        $this->assertTrue(is_a(EloquentInventoryLevelRepository::class, InventoryLevelRepositoryInterface::class, true));
        $this->assertTrue(is_a(EloquentInventoryValuationLayerRepository::class, InventoryValuationLayerRepositoryInterface::class, true));
        $this->assertTrue(is_a(EloquentInventoryCycleCountRepository::class, InventoryCycleCountRepositoryInterface::class, true));
        $this->assertTrue(is_a(EloquentInventoryCycleCountLineRepository::class, InventoryCycleCountLineRepositoryInterface::class, true));
    }

    // ──────────────────────────── Controllers ────────────────────────────────

    /** @test */
    public function test_inventory_setting_controller_injects_services(): void
    {
        $r = new \ReflectionClass(InventorySettingController::class);
        $c = $r->getConstructor();
        $params = array_map(fn ($p) => (string) $p->getType(), $c->getParameters());
        $this->assertContains(FindInventorySettingServiceInterface::class, $params);
        $this->assertContains(CreateInventorySettingServiceInterface::class, $params);
        $this->assertContains(UpdateInventorySettingServiceInterface::class, $params);
    }

    /** @test */
    public function test_inventory_location_controller_injects_four_services(): void
    {
        $r      = new \ReflectionClass(InventoryLocationController::class);
        $params = array_map(fn ($p) => (string) $p->getType(), $r->getConstructor()->getParameters());
        $this->assertContains(FindInventoryLocationServiceInterface::class, $params);
        $this->assertContains(CreateInventoryLocationServiceInterface::class, $params);
        $this->assertContains(UpdateInventoryLocationServiceInterface::class, $params);
        $this->assertContains(DeleteInventoryLocationServiceInterface::class, $params);
    }

    /** @test */
    public function test_inventory_batch_controller_injects_four_services(): void
    {
        $r      = new \ReflectionClass(InventoryBatchController::class);
        $params = array_map(fn ($p) => (string) $p->getType(), $r->getConstructor()->getParameters());
        $this->assertContains(FindInventoryBatchServiceInterface::class, $params);
        $this->assertContains(CreateInventoryBatchServiceInterface::class, $params);
        $this->assertContains(UpdateInventoryBatchServiceInterface::class, $params);
        $this->assertContains(DeleteInventoryBatchServiceInterface::class, $params);
    }

    /** @test */
    public function test_inventory_cycle_count_controller_injects_four_services(): void
    {
        $r      = new \ReflectionClass(InventoryCycleCountController::class);
        $params = array_map(fn ($p) => (string) $p->getType(), $r->getConstructor()->getParameters());
        $this->assertContains(FindInventoryCycleCountServiceInterface::class, $params);
        $this->assertContains(CreateInventoryCycleCountServiceInterface::class, $params);
    }

    // ──────────────────────────── Form Requests ───────────────────────────────

    /** @test */
    public function test_store_inventory_setting_request_rules(): void
    {
        $r = new StoreInventorySettingRequest();
        $this->assertArrayHasKey('tenant_id', $r->rules());
        $this->assertArrayHasKey('valuation_method', $r->rules());
    }

    /** @test */
    public function test_store_inventory_batch_request_rules(): void
    {
        $r = new StoreInventoryBatchRequest();
        $this->assertArrayHasKey('batch_number', $r->rules());
        $this->assertArrayHasKey('product_id', $r->rules());
    }

    /** @test */
    public function test_store_inventory_location_request_rules(): void
    {
        $r = new StoreInventoryLocationRequest();
        $this->assertArrayHasKey('warehouse_id', $r->rules());
        $this->assertArrayHasKey('name', $r->rules());
    }

    /** @test */
    public function test_store_inventory_cycle_count_request_rules(): void
    {
        $r = new StoreInventoryCycleCountRequest();
        $this->assertArrayHasKey('reference_number', $r->rules());
        $this->assertArrayHasKey('warehouse_id', $r->rules());
    }

    /** @test */
    public function test_store_inventory_serial_request_rules(): void
    {
        $r = new StoreInventorySerialNumberRequest();
        $this->assertArrayHasKey('serial_number', $r->rules());
    }

    /** @test */
    public function test_store_inventory_level_request_rules(): void
    {
        $r = new StoreInventoryLevelRequest();
        $this->assertArrayHasKey('product_id', $r->rules());
    }

    /** @test */
    public function test_store_valuation_layer_request_rules(): void
    {
        $r = new StoreInventoryValuationLayerRequest();
        $this->assertArrayHasKey('qty_in', $r->rules());
        $this->assertArrayHasKey('unit_cost', $r->rules());
    }

    /** @test */
    public function test_store_cycle_count_line_request_rules(): void
    {
        $r = new StoreInventoryCycleCountLineRequest();
        $this->assertArrayHasKey('cycle_count_id', $r->rules());
        $this->assertArrayHasKey('product_id', $r->rules());
    }

    // ──────────────────────────── Resources ───────────────────────────────────

    /** @test */
    public function test_inventory_setting_resource_returns_expected_keys(): void
    {
        $s    = $this->makeSetting();
        $res  = new InventorySettingResource($s);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('valuation_method', $data);
        $this->assertArrayHasKey('management_method', $data);
        $this->assertArrayHasKey('track_lots', $data);
    }

    /** @test */
    public function test_inventory_location_resource_returns_expected_keys(): void
    {
        $l    = $this->makeLocation();
        $res  = new InventoryLocationResource($l);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('warehouse_id', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('is_pickable', $data);
    }

    /** @test */
    public function test_inventory_batch_resource_returns_expected_keys(): void
    {
        $b    = $this->makeBatch();
        $res  = new InventoryBatchResource($b);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('batch_number', $data);
        $this->assertArrayHasKey('remaining_qty', $data);
        $this->assertArrayHasKey('status', $data);
    }

    /** @test */
    public function test_inventory_serial_number_resource_returns_expected_keys(): void
    {
        $s    = $this->makeSerial();
        $res  = new InventorySerialNumberResource($s);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('serial_number', $data);
        $this->assertArrayHasKey('status', $data);
    }

    /** @test */
    public function test_inventory_level_resource_returns_expected_keys(): void
    {
        $l    = $this->makeLevel();
        $res  = new InventoryLevelResource($l);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('qty_on_hand', $data);
        $this->assertArrayHasKey('qty_available', $data);
        $this->assertArrayHasKey('is_low_stock', $data);
    }

    /** @test */
    public function test_inventory_valuation_layer_resource_returns_expected_keys(): void
    {
        $v    = $this->makeValuationLayer();
        $res  = new InventoryValuationLayerResource($v);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('valuation_method', $data);
        $this->assertArrayHasKey('qty_remaining', $data);
        $this->assertArrayHasKey('total_value', $data);
    }

    /** @test */
    public function test_inventory_cycle_count_resource_returns_expected_keys(): void
    {
        $c    = $this->makeCycleCount();
        $res  = new InventoryCycleCountResource($c);
        $data = $res->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('reference_number', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('count_method', $data);
    }

    // ──────────────────────────── Collections ─────────────────────────────────

    /** @test */
    public function test_inventory_location_collection_uses_correct_resource(): void
    {
        $this->assertSame(InventoryLocationResource::class, InventoryLocationCollection::class::$collects ?? (new InventoryLocationCollection([]))->collects);
    }

    /** @test */
    public function test_inventory_batch_collection_uses_correct_resource(): void
    {
        $this->assertSame(InventoryBatchResource::class, (new InventoryBatchCollection([]))->collects);
    }

    /** @test */
    public function test_inventory_cycle_count_collection_uses_correct_resource(): void
    {
        $this->assertSame(InventoryCycleCountResource::class, (new InventoryCycleCountCollection([]))->collects);
    }

    // ──────────────────────────── Service Provider ─────────────────────────────

    /** @test */
    public function test_inventory_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(InventoryServiceProvider::class));
    }

    /** @test */
    public function test_inventory_service_provider_has_register_method(): void
    {
        $this->assertTrue(method_exists(InventoryServiceProvider::class, 'register'));
    }

    /** @test */
    public function test_inventory_service_provider_has_boot_method(): void
    {
        $this->assertTrue(method_exists(InventoryServiceProvider::class, 'boot'));
    }
}
