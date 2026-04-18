<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'batches_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(null, 'id', 'batches_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'batches_variant_id_fk')->nullOnDelete();
            $table->string('batch_number');
            $table->string('lot_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('received_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained(null, 'id', 'batches_supplier_id_fk')->nullOnDelete();
            $table->enum('status', ['active', 'quarantine', 'expired', 'depleted'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id', 'variant_id', 'batch_number'], 'batches_tenant_product_batch_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
