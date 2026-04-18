<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'serials_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(null, 'id', 'serials_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'serials_variant_id_fk')->nullOnDelete();
            $table->string('serial_number');
            $table->foreignId('batch_id')->nullable()->constrained(null, 'id', 'serials_batch_id_fk')->nullOnDelete();
            $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'scrapped', 'in_transit'])->default('available');
            $table->foreignId('current_location_id')->nullable()->constrained('warehouse_locations', 'id', 'serials_current_location_id_fk')->nullOnDelete();
            $table->nullableMorphs('current_owner'); // e.g., customer, supplier, employee            $table->date('manufacture_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'serial_number'], 'serials_tenant_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};
