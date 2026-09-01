<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_note_line_batch_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'grn_line_batch_allocations_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('goods_receipt_note_line_id');
            $table->foreignId('batch_id');
            $table->decimal('quantity', 20, 6);
            $table->decimal('base_quantity', 20, 6);
            $table->foreignId('inventory_movement_id')->nullable();
            $table->timestamps();

            $table->unique(['goods_receipt_note_line_id', 'batch_id'], 'grn_line_batch_allocations_line_batch_uk');
            $table->unique(['id', 'tenant_id'], 'grn_line_batch_allocations_id_tenant_uk');
            $table->index('batch_id', 'grn_line_batch_allocations_batch_ix');
            $table->index('inventory_movement_id', 'grn_line_batch_allocations_movement_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'grn_line_batch_allocations_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['goods_receipt_note_line_id', 'tenant_id'], 'grn_line_batch_allocations_line_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('goods_receipt_note_lines')
                ->cascadeOnDelete();
            $table->foreign(['batch_id', 'tenant_id'], 'grn_line_batch_allocations_batch_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_batches')
                ->restrictOnDelete();
            $table->foreign(['inventory_movement_id', 'tenant_id'], 'grn_line_batch_allocations_movement_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_line_batch_allocations');
    }
};
