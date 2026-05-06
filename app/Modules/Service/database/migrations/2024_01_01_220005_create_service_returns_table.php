<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_returns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('return_number');
            $table->foreignId('job_card_id')->nullable()->constrained('service_job_cards')->nullOnDelete();
            $table->enum('return_type', ['part','core','warranty'])->default('part');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 20, 6);
            $table->text('return_reason');
            $table->decimal('refund_amount', 20, 6)->nullable();
            $table->decimal('restocking_fee', 20, 6)->nullable();
            $table->foreignId('inventory_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->enum('status', ['pending','received','inspected','refunded'])->default('pending');
            $table->timestamps();
            $table->unique(['tenant_id','return_number'], 'service_returns_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_returns');
    }
};
