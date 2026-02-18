<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bill of Materials table
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->string('bom_number')->unique();
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('is_active');
            $table->index('bom_number');
        });

        // Bill of Material Items table
        Schema::create('bill_of_material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('bill_of_material_id')->constrained()->onDelete('cascade');
            $table->foreignId('component_product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity', 15, 4);
            $table->foreignId('uom_id')->nullable()->constrained('units')->onDelete('restrict');
            $table->decimal('scrap_factor', 5, 2)->default(0)->comment('Percentage of waste/scrap');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('bill_of_material_id');
            $table->index('component_product_id');
        });

        // Production Orders table
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('production_order_number')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('bill_of_material_id')->nullable()->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 4);
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('restrict');
            $table->dateTime('scheduled_start_date')->nullable();
            $table->dateTime('scheduled_end_date')->nullable();
            $table->dateTime('actual_start_date')->nullable();
            $table->dateTime('actual_end_date')->nullable();
            $table->string('status')->default('draft');
            $table->string('priority')->default('normal');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('released_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('priority');
            $table->index('scheduled_start_date');
            $table->index('production_order_number');
        });

        // Production Order Items (Material consumption tracking)
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('production_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('consumed_quantity', 15, 4)->default(0);
            $table->foreignId('uom_id')->nullable()->constrained('units')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('production_order_id');
            $table->index('product_id');
        });

        // Work Orders table
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('work_order_number')->unique();
            $table->foreignId('production_order_id')->constrained()->onDelete('cascade');
            $table->string('workstation')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('started_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('production_order_id');
            $table->index('status');
            $table->index('scheduled_start');
            $table->index('work_order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('production_order_items');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bill_of_material_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
