<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('job_card_id')->constrained('vehicle_job_cards', 'id')->cascadeOnDelete();
            $table->string('task_name', 255);
            $table->enum('task_status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('estimated_hours', 20, 6)->default('0.000000');
            $table->decimal('actual_hours', 20, 6)->default('0.000000');
            $table->decimal('labor_rate', 20, 6)->default('0.000000');
            $table->decimal('labor_cost', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'job_card_id', 'task_status'], 'vehicle_service_tasks_tenant_job_status_idx');
        });

        Schema::create('vehicle_service_part_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('job_card_id')->constrained('vehicle_job_cards', 'id')->cascadeOnDelete();
            $table->foreignId('service_task_id')->nullable()->constrained('vehicle_service_tasks', 'id')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products', 'id')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure', 'id')->nullOnDelete();
            $table->decimal('quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6)->default('0.000000');
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_parts_tenant_job_idx');
            $table->index(['tenant_id', 'product_id'], 'vehicle_service_parts_tenant_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_part_usages');
        Schema::dropIfExists('vehicle_service_tasks');
    }
};
