<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->string('transfer_number');
            $table->string('status')->default('DRAFT')->comment('DRAFT, PENDING, COMPLETED, CANCELLED');
            $table->date('request_date');
            $table->date('expected_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'transfer_number'], 'transfer_orders_transfer_number_uk');
            $table->index(['tenant_id', 'status'], 'transfer_orders_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_orders');
    }
};
