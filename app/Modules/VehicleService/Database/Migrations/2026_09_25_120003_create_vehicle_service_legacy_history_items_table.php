<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_legacy_history_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vs_legacy_items_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('legacy_history_id');
            $table->string('source_record_id', 100);
            $table->char('source_payload_sha256', 64);
            $table->unsignedInteger('line_number');
            $table->string('category', 20);
            $table->string('source_type_raw', 100)->nullable();
            $table->foreignId('item_id')->nullable();
            $table->foreignId('uom_id')->nullable();
            $table->string('item_code_snapshot', 100)->nullable();
            $table->string('item_name_snapshot')->nullable();
            $table->text('description');
            $table->decimal('quantity', 20, 6);
            $table->string('uom_snapshot', 100)->nullable();
            $table->json('source_payload');
            $table->timestamp('imported_at');

            $table->unique(['legacy_history_id', 'source_record_id'], 'vs_legacy_items_source_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'legacy_history_id', 'line_number'], 'vs_legacy_items_history_line_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vs_legacy_items_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['legacy_history_id', 'tenant_id'], 'vs_legacy_items_history_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_legacy_histories')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'vs_legacy_items_item_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'vs_legacy_items_uom_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_legacy_history_items');
    }
};
