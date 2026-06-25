<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_custody_event_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('custody_event_id');
            $table->unsignedInteger('sequence');
            $table->string('item_type', 30);
            $table->string('item_code', 50)->nullable();
            $table->text('description');
            $table->decimal('expected_quantity', 20, 6)->nullable();
            $table->decimal('actual_quantity', 20, 6)->nullable();
            $table->string('condition_status', 30)->nullable();
            $table->boolean('is_chargeable')->default(false);
            $table->decimal('estimated_amount', 20, 6)->default('0.000000');
            $table->string('responsible_side', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['custody_event_id', 'sequence'], 'rental_custody_event_items_sequence_uk');
            $table->index(['custody_event_id', 'item_type'], 'rental_custody_event_items_type_idx');

            $table->unique(['id', 'tenant_id'], 'rental_custody_event_items_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_custody_event_items_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['custody_event_id', 'tenant_id'], 'rental_custody_event_items_custody_event_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_custody_events')
                ->cascadeOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_custody_event_items_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_custody_event_items_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_custody_event_items');
    }
};
