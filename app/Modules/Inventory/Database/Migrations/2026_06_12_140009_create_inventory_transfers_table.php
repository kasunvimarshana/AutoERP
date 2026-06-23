<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('transfer_number', 80);
            $table->date('transfer_date');
            $table->foreignId('from_warehouse_id');
            $table->foreignId('from_warehouse_location_id')->nullable();
            $table->foreignId('to_warehouse_id');
            $table->foreignId('to_warehouse_location_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'transfer_number'], 'inventory_transfers_tenant_number_uk');

            $table->unique(['id', 'tenant_id'], 'inventory_transfers_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_transfers_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['from_warehouse_id', 'tenant_id'], 'inventory_transfers_from_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['from_warehouse_location_id', 'tenant_id'], 'inventory_transfers_from_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
            $table->foreign(['to_warehouse_id', 'tenant_id'], 'inventory_transfers_to_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['to_warehouse_location_id', 'tenant_id'], 'inventory_transfers_to_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'inventory_transfers_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'inventory_transfers_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['posted_by', 'tenant_id'], 'inventory_transfers_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['received_by', 'tenant_id'], 'inventory_transfers_received_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'inventory_transfers_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['cancelled_by', 'tenant_id'], 'inventory_transfers_cancelled_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
