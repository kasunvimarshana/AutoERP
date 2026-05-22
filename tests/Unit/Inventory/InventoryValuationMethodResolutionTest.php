<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Inventory\Application\DTOs\ValuationRequest;
use Modules\Inventory\Application\DTOs\ValuationResult;
use Modules\Inventory\Application\Factories\ValuationStrategyFactory;
use Modules\Inventory\Application\Services\InventoryValuationService;
use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Enums\StockDirection;
use Tests\TestCase;

class InventoryValuationMethodResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();

        config(['inventory.valuation.default_method' => 'FIFO']);
    }

    public function testRequestOverrideHasHighestPriority(): void
    {
        $this->seedItem(1001, 'LIFO');
        $this->seedValuationConfig(1001, 'FIFO');

        $service = $this->makeService();

        $result = $service->process($this->makeInRequest(1001, 'WEIGHTED_AVERAGE'));

        $this->assertSame('WEIGHTED_AVERAGE', $result->valuationMethod);
    }

    public function testValuationConfigIsUsedWhenRequestOverrideIsMissing(): void
    {
        $this->seedItem(1002, 'FIFO');
        $this->seedValuationConfig(1002, 'LIFO');

        $service = $this->makeService();

        $result = $service->process($this->makeInRequest(1002, null));

        $this->assertSame('LIFO', $result->valuationMethod);
    }

    public function testItemMethodIsUsedWhenConfigAndRequestAreMissing(): void
    {
        $this->seedItem(1003, 'LIFO');

        $service = $this->makeService();

        $result = $service->process($this->makeInRequest(1003, null));

        $this->assertSame('LIFO', $result->valuationMethod);
    }

    public function testDefaultConfigMethodIsUsedWhenNoOtherMethodExists(): void
    {
        $this->seedItem(1004, null);
        config(['inventory.valuation.default_method' => 'WEIGHTED_AVERAGE']);

        $service = $this->makeService();

        $result = $service->process($this->makeInRequest(1004, null));

        $this->assertSame('WEIGHTED_AVERAGE', $result->valuationMethod);
    }

    private function makeService(): InventoryValuationService
    {
        $factory = new ValuationStrategyFactory(app(), [
            'FIFO' => TestFifoValuationStrategy::class,
            'LIFO' => TestLifoValuationStrategy::class,
            'WEIGHTED_AVERAGE' => TestWeightedAverageValuationStrategy::class,
        ]);

        return new InventoryValuationService($factory);
    }

    private function makeInRequest(int $itemId, ?string $method): ValuationRequest
    {
        return new ValuationRequest(
            tenantId: 1,
            itemId: $itemId,
            locationId: 5,
            uomId: 1,
            direction: StockDirection::IN,
            quantity: 2,
            warehouseId: 2,
            organizationUnitId: 1,
            unitCost: 10,
            txnType: 'OPENING_STOCK',
            valuationMethod: $method,
        );
    }

    private function seedItem(int $itemId, ?string $method): void
    {
        DB::table('items')->insert([
            'id' => $itemId,
            'valuation_method' => $method,
        ]);
    }

    private function seedValuationConfig(int $itemId, string $method): void
    {
        DB::table('valuation_configs')->insert([
            'tenant_id' => 1,
            'item_id' => $itemId,
            'warehouse_id' => 2,
            'transaction_type' => 'OPENING_STOCK',
            'valuation_method' => $method,
            'is_active' => true,
        ]);
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('inventory_cost_layers');
        Schema::dropIfExists('valuation_configs');
        Schema::dropIfExists('items');

        Schema::create('items', static function (Blueprint $table): void {
            $table->id();
            $table->string('valuation_method')->nullable();
        });

        Schema::create('valuation_configs', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('valuation_method')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('inventory_cost_layers', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->string('valuation_method')->nullable();
            $table->decimal('quantity_remaining', 20, 4)->default(0);
            $table->decimal('quantity_in', 20, 4)->default(0);
            $table->boolean('is_closed')->default(false);
            $table->date('layer_date')->nullable();
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_levels', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity_on_hand', 20, 4)->default(0);
            $table->decimal('quantity_reserved', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->string('condition')->default('good');
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();
        });

        Schema::create('stock_movements', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('direction');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('txn_type')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('quantity_in', 20, 4)->default(0);
            $table->decimal('quantity_out', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->decimal('balance_quantity', 20, 4)->default(0);
            $table->decimal('balance_value', 20, 4)->default(0);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
}

final class TestFifoValuationStrategy implements ValuationStrategyInterface
{
    public function calculate(ValuationRequest $request): ValuationResult
    {
        return new ValuationResult(
            valuationMethod: 'FIFO',
            direction: strtoupper($request->direction),
            quantity: $request->quantity,
            unitCost: (float) ($request->unitCost ?? 0),
            totalCost: $request->quantity * (float) ($request->unitCost ?? 0),
        );
    }
}

final class TestLifoValuationStrategy implements ValuationStrategyInterface
{
    public function calculate(ValuationRequest $request): ValuationResult
    {
        return new ValuationResult(
            valuationMethod: 'LIFO',
            direction: strtoupper($request->direction),
            quantity: $request->quantity,
            unitCost: (float) ($request->unitCost ?? 0),
            totalCost: $request->quantity * (float) ($request->unitCost ?? 0),
        );
    }
}

final class TestWeightedAverageValuationStrategy implements ValuationStrategyInterface
{
    public function calculate(ValuationRequest $request): ValuationResult
    {
        return new ValuationResult(
            valuationMethod: 'WEIGHTED_AVERAGE',
            direction: strtoupper($request->direction),
            quantity: $request->quantity,
            unitCost: (float) ($request->unitCost ?? 0),
            totalCost: $request->quantity * (float) ($request->unitCost ?? 0),
        );
    }
}
