<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('asset_id')->index();
            $table->uuid('assigned_technician_id')->nullable()->index();
            $table->string('order_number')->unique();
            $table->string('service_type', 100);
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft')->index();
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('estimated_cost', 20, 6)->default(0);
            $table->decimal('total_cost', 20, 6)->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_tasks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('service_order_id')->index();
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->decimal('labor_cost', 20, 6)->default(0);
            $table->unsignedInteger('labor_minutes')->nullable();
            $table->timestamps();

            $table->foreign('service_order_id')
                ->references('id')
                ->on('service_orders')
                ->cascadeOnDelete();
        });

        Schema::create('service_part_usages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('service_order_id')->index();
            $table->uuid('inventory_item_id')->nullable()->index();
            $table->string('part_name');
            $table->string('part_number', 100);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->timestamps();

            $table->foreign('service_order_id')
                ->references('id')
                ->on('service_orders')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_part_usages');
        Schema::dropIfExists('service_tasks');
        Schema::dropIfExists('service_orders');
    }
};
