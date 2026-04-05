<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('order_number');
            $table->string('type', 20)->default('corrective');
            $table->string('status', 30)->default('draft');
            $table->string('priority', 20)->default('medium');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->decimal('labor_cost', 15, 4)->default(0);
            $table->decimal('parts_cost', 15, 4)->default(0);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status', 'type']);
            $table->index(['tenant_id', 'scheduled_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('service_orders'); }
};
