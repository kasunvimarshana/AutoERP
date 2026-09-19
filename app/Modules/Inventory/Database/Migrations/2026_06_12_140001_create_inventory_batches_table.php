<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'inventory_batches_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->string('batch_number', 120);
            $table->string('lot_number', 120)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'item_id', 'batch_number'], 'inventory_batches_tenant_item_batch_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_batches_tenant_org_ix');
            $table->index('item_id', 'inventory_batches_item_ix');
            $table->index('item_variant_id', 'inventory_batches_variant_ix');
            $table->index('batch_number', 'inventory_batches_batch_ix');
            $table->index('lot_number', 'inventory_batches_lot_ix');
            $table->index('status', 'inventory_batches_status_ix');

            $table->unique(['id', 'tenant_id'], 'inventory_batches_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_batches_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'inventory_batches_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'inventory_batches_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
