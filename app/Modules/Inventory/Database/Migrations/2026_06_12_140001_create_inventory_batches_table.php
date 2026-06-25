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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->restrictOnDelete();
            $table->string('batch_number', 120);
            $table->string('lot_number', 120)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'item_id', 'batch_number'], 'inventory_batches_tenant_item_batch_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_batches_tenant_org_idx');
            $table->index('item_id', 'inventory_batches_item_idx');
            $table->index('item_variant_id', 'inventory_batches_variant_idx');
            $table->index('batch_number', 'inventory_batches_batch_idx');
            $table->index('lot_number', 'inventory_batches_lot_idx');
            $table->index('status', 'inventory_batches_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
