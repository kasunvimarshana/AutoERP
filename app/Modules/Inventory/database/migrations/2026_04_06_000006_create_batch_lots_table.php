<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->string('status', 30)->default('active'); // active, quarantine, expired
            $table->string('supplier_id', 36)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'batch_number']);
            $table->index(['product_id', 'lot_number']);
            $table->index(['tenant_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_lots');
    }
};
