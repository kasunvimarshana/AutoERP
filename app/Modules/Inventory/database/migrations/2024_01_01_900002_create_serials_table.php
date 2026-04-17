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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('serial_number');
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'scrapped', 'in_transit'])->default('available');
            $table->foreignId('current_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->nullableMorphs('current_owner'); // e.g., customer, supplier, employee
            $table->date('manufacture_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'serial_number'], 'uq_serials_tenant_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};