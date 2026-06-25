<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_base_uom_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('old_base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('new_base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->decimal('conversion_factor', 20, 6);
            $table->timestamp('effective_at');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('validation_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_base_uom_revisions_scope_idx');
            $table->index(['item_id', 'effective_at'], 'item_base_uom_revisions_item_effective_idx');
            $table->index('status', 'item_base_uom_revisions_status_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('item_base_uom_revisions');
    }
};
