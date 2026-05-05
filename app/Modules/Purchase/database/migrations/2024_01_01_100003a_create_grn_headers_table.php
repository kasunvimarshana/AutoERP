<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('supplier_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('purchase_order_id')->nullable()->constrained(null, 'id', 'grn_headers_purchase_order_id_fk')->nullOnDelete();
            $table->string('grn_number');
            $table->enum('status', ['draft', 'partial', 'complete', 'posted'])->default('draft');
            $table->date('received_date');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'grn_headers_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by');

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'grn_number'], 'grn_headers_tenant_grn_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_headers');
    }
};
