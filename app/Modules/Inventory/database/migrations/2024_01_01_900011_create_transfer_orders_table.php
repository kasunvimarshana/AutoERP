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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('from_warehouse_id')->constrained('warehouses', 'id', 'transfer_orders_from_warehouse_id_fk')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses', 'id', 'transfer_orders_to_warehouse_id_fk')->cascadeOnDelete();
            $table->string('transfer_number');
            $table->enum('status', ['draft', 'approved', 'in_transit', 'received', 'cancelled'])->default('draft');
            $table->date('request_date');
            $table->date('expected_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'transfer_number'], 'transfer_orders_tenant_transfer_number_uk');
            $table->index(['tenant_id', 'status'], 'transfer_orders_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_orders');
    }
};
