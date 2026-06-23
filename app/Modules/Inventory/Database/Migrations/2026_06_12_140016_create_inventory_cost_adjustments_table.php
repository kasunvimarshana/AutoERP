<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cost_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('adjustment_number', 80);
            $table->date('adjustment_date');
            $table->string('status', 30)->default('draft');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'adjustment_number'], 'inventory_cost_adjustments_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_cost_adjustments_scope_idx');
            $table->index('status', 'inventory_cost_adjustments_status_idx');

            $table->unique(['id', 'tenant_id'], 'inventory_cost_adjustments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'inventory_cost_adjustments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'inventory_cost_adjustments_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['posted_by', 'tenant_id'], 'inventory_cost_adjustments_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_adjustments');
    }
};
