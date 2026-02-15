<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('bom_id')->nullable()->constrained('bill_of_materials')->onDelete('set null');
            $table->string('work_order_number', 50)->unique();
            $table->string('reference_number', 100)->nullable();
            $table->enum('status', ['draft', 'planned', 'released', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('produced_quantity', 15, 4)->default(0);
            $table->decimal('scrap_quantity', 15, 4)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units_of_measure')->onDelete('set null');
            $table->date('planned_start_date');
            $table->date('planned_end_date')->nullable();
            $table->datetime('actual_start_date')->nullable();
            $table->datetime('actual_end_date')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->decimal('material_cost', 15, 2)->default(0);
            $table->decimal('labor_cost', 15, 2)->default(0);
            $table->decimal('overhead_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('production_instructions')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'work_order_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'branch_id']);
            $table->index(['planned_start_date', 'planned_end_date']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
