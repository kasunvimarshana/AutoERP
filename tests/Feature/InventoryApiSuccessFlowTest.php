<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryApiSuccessFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->seedReferenceData();
    }

    public function testInventoryValuatePersistsStockLevelLayerAndMovement(): void
    {
        $response = $this->postJson('/api/inventory/valuate', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'uom_id' => 1,
            'direction' => 'IN',
            'quantity' => 5,
            'warehouse_id' => 3,
            'unit_cost' => 12.5,
            'txn_type' => 'OPENING_STOCK',
            'reference_type' => 'PURCHASE_RECEIPT',
            'reference_id' => 55,
            'valuation_method' => 'FIFO',
        ]);

        $response
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('valuation_method', 'FIFO')
            ->assertJsonPath('direction', 'IN')
            ->assertJsonPath('quantity', 5)
            ->assertJsonPath('unit_cost', 12.5)
            ->assertJsonPath('total_cost', 62.5)
            ->assertJsonPath('balance_quantity', 5)
            ->assertJsonPath('balance_value', 62.5);

        $this->assertDatabaseHas('stock_levels', [
            'tenant_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'uom_id' => 1,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'unit_cost' => 12.5,
        ]);

        $this->assertDatabaseHas('inventory_cost_layers', [
            'tenant_id' => 1,
            'item_id' => 100,
            'warehouse_id' => 3,
            'location_id' => 10,
            'valuation_method' => 'FIFO',
            'quantity_in' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 12.5,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'direction' => 'in',
            'txn_type' => 'OPENING_STOCK',
            'quantity' => 5,
            'quantity_in' => 5,
            'quantity_out' => 0,
            'unit_cost' => 12.5,
            'total_cost' => 62.5,
            'balance_quantity' => 5,
            'balance_value' => 62.5,
        ]);
    }

    public function testInventoryValuateOutboundConsumesLayersAndClosesConsumedLayer(): void
    {
        DB::table('stock_levels')->insert([
            'id' => 510,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'variant_id' => null,
            'location_id' => 10,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 1,
            'quantity_on_hand' => 8,
            'quantity_reserved' => 0,
            'unit_cost' => 12,
            'last_movement_at' => null,
            'condition' => 'good',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_cost_layers')->insert([
            'id' => 801,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'location_id' => 10,
            'warehouse_id' => 3,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'valuation_method' => 'FIFO',
            'quantity_remaining' => 3,
            'quantity_in' => 3,
            'is_closed' => false,
            'layer_date' => '2026-01-01',
            'unit_cost' => 10,
            'reference_type' => null,
            'reference_id' => null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_cost_layers')->insert([
            'id' => 802,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'location_id' => 10,
            'warehouse_id' => 3,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'valuation_method' => 'FIFO',
            'quantity_remaining' => 5,
            'quantity_in' => 5,
            'is_closed' => false,
            'layer_date' => '2026-01-02',
            'unit_cost' => 20,
            'reference_type' => null,
            'reference_id' => null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/inventory/valuate', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'uom_id' => 1,
            'direction' => 'OUT',
            'quantity' => 3,
            'warehouse_id' => 3,
            'txn_type' => 'SALES_ISSUE',
            'reference_type' => 'SALES_ORDER',
            'reference_id' => 4001,
            'valuation_method' => 'FIFO',
        ]);

        $response
            ->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('valuation_method', 'FIFO')
            ->assertJsonPath('direction', 'OUT')
            ->assertJsonPath('quantity', 3)
            ->assertJsonPath('unit_cost', 10)
            ->assertJsonPath('total_cost', 30)
            ->assertJsonPath('balance_quantity', 5)
            ->assertJsonPath('balance_value', 60)
            ->assertJsonPath('consumptions.0.layer_id', 801)
            ->assertJsonPath('consumptions.0.consumed_quantity', 3)
            ->assertJsonPath('consumptions.0.unit_cost', 10);

        $this->assertDatabaseHas('inventory_cost_layers', [
            'id' => 801,
            'quantity_remaining' => 0,
            'is_closed' => 1,
            'row_version' => 2,
        ]);

        $this->assertDatabaseHas('inventory_cost_layers', [
            'id' => 802,
            'quantity_remaining' => 5,
            'is_closed' => 0,
            'row_version' => 1,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'id' => 510,
            'quantity_on_hand' => 5,
            'row_version' => 2,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'direction' => 'out',
            'txn_type' => 'SALES_ISSUE',
            'quantity' => 3,
            'quantity_in' => 0,
            'quantity_out' => 3,
            'unit_cost' => 10,
            'total_cost' => 30,
            'balance_quantity' => 5,
            'balance_value' => 60,
        ]);
    }

    public function testInventoryValuateOutboundInsufficientLayersReturnsValidationErrorAndRollsBack(): void
    {
        DB::table('stock_levels')->insert([
            'id' => 520,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'variant_id' => null,
            'location_id' => 10,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 1,
            'quantity_on_hand' => 8,
            'quantity_reserved' => 0,
            'unit_cost' => 10,
            'last_movement_at' => null,
            'condition' => 'good',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_cost_layers')->insert([
            'id' => 803,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'location_id' => 10,
            'warehouse_id' => 3,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'valuation_method' => 'FIFO',
            'quantity_remaining' => 3,
            'quantity_in' => 3,
            'is_closed' => false,
            'layer_date' => '2026-01-03',
            'unit_cost' => 10,
            'reference_type' => null,
            'reference_id' => null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/inventory/valuate', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'uom_id' => 1,
            'direction' => 'OUT',
            'quantity' => 4,
            'warehouse_id' => 3,
            'txn_type' => 'SALES_ISSUE_FAIL',
            'reference_type' => 'SALES_ORDER',
            'reference_id' => 4002,
            'valuation_method' => 'FIFO',
        ]);

        $response
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', 'Insufficient inventory layers for FIFO valuation.');

        $this->assertDatabaseHas('inventory_cost_layers', [
            'id' => 803,
            'quantity_remaining' => 3,
            'is_closed' => 0,
            'row_version' => 1,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'id' => 520,
            'quantity_on_hand' => 8,
            'row_version' => 1,
        ]);

        $this->assertDatabaseMissing('stock_movements', [
            'tenant_id' => 1,
            'item_id' => 100,
            'txn_type' => 'SALES_ISSUE_FAIL',
        ]);
    }

    public function testInventoryValuateOutboundInsufficientStockLevelReturnsValidationErrorAndRollsBack(): void
    {
        DB::table('stock_levels')->insert([
            'id' => 530,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'variant_id' => null,
            'location_id' => 10,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => 1,
            'quantity_on_hand' => 2,
            'quantity_reserved' => 0,
            'unit_cost' => 10,
            'last_movement_at' => null,
            'condition' => 'good',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_cost_layers')->insert([
            'id' => 804,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'location_id' => 10,
            'warehouse_id' => 3,
            'variant_id' => null,
            'batch_id' => null,
            'serial_id' => null,
            'valuation_method' => 'FIFO',
            'quantity_remaining' => 6,
            'quantity_in' => 6,
            'is_closed' => false,
            'layer_date' => '2026-01-04',
            'unit_cost' => 10,
            'reference_type' => null,
            'reference_id' => null,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/inventory/valuate', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'uom_id' => 1,
            'direction' => 'OUT',
            'quantity' => 4,
            'warehouse_id' => 3,
            'txn_type' => 'SALES_ISSUE_LOW_LEVEL',
            'reference_type' => 'SALES_ORDER',
            'reference_id' => 4003,
            'valuation_method' => 'FIFO',
        ]);

        $response
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', 'Insufficient stock level for outbound movement.');

        $this->assertDatabaseHas('inventory_cost_layers', [
            'id' => 804,
            'quantity_remaining' => 6,
            'is_closed' => 0,
            'row_version' => 1,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'id' => 530,
            'quantity_on_hand' => 2,
            'row_version' => 1,
        ]);

        $this->assertDatabaseMissing('stock_movements', [
            'tenant_id' => 1,
            'item_id' => 100,
            'txn_type' => 'SALES_ISSUE_LOW_LEVEL',
        ]);
    }

    public function testInventoryAllocatePersistsReservationAndUpdatesReservedQuantity(): void
    {
        DB::table('stock_levels')->insert([
            'id' => 500,
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'metadata' => null,
            'item_id' => 100,
            'variant_id' => null,
            'location_id' => 10,
            'batch_id' => 700,
            'serial_id' => null,
            'uom_id' => 1,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 2,
            'unit_cost' => 7,
            'last_movement_at' => null,
            'condition' => 'good',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/inventory/allocate', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_id' => 100,
            'required_quantity' => 4,
            'location_id' => 10,
            'allocation_method' => 'QUANTITY',
            'reference_type' => 'SALES_ORDER',
            'reference_id' => 9001,
            'persist_reservation' => true,
            'expires_at' => '2026-12-31 00:00:00',
        ]);

        $response
            ->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('allocation_method', 'QUANTITY')
            ->assertJsonPath('requested_quantity', 4)
            ->assertJsonPath('allocated_quantity', 4)
            ->assertJsonPath('fully_allocated', true)
            ->assertJsonPath('lines.0.stock_level_id', 500)
            ->assertJsonPath('lines.0.location_id', 10)
            ->assertJsonPath('lines.0.batch_id', 700)
            ->assertJsonPath('lines.0.quantity', 4)
            ->assertJsonPath('lines.0.unit_cost', 7);

        $this->assertDatabaseHas('stock_reservations', [
            'tenant_id' => 1,
            'item_id' => 100,
            'location_id' => 10,
            'batch_id' => 700,
            'quantity' => 4,
            'reserved_for_type' => 'SALES_ORDER',
            'reserved_for_id' => 9001,
            'unit_cost' => 7,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'id' => 500,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 6,
            'row_version' => 2,
        ]);
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert(['id' => 1]);
        DB::table('organization_units')->insert(['id' => 1]);
        DB::table('warehouses')->insert(['id' => 3]);
        DB::table('warehouse_locations')->insert(['id' => 10, 'warehouse_id' => 3]);
        DB::table('unit_of_measures')->insert(['id' => 1]);
        DB::table('items')->insert([
            'id' => 100,
            'valuation_method' => 'FIFO',
            'allocation_method' => 'QUANTITY',
        ]);
        DB::table('batches')->insert([
            'id' => 700,
            'batch_number' => 'B-700',
            'lot_number' => 'L-700',
            'expiry_date' => '2027-01-31',
        ]);
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('inventory_cost_layers');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('items');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('organization_units');
        Schema::dropIfExists('tenants');

        Schema::create('tenants', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('organization_units', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('warehouses', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('warehouse_locations', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
        });

        Schema::create('unit_of_measures', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('items', static function (Blueprint $table): void {
            $table->id();
            $table->string('valuation_method')->nullable();
            $table->string('allocation_method')->nullable();
        });

        Schema::create('batches', static function (Blueprint $table): void {
            $table->id();
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
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
            $table->unsignedBigInteger('row_version')->default(1);
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

        Schema::create('stock_reservations', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->string('reserved_for_type')->nullable();
            $table->unsignedBigInteger('reserved_for_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->timestamps();
        });
    }
}
