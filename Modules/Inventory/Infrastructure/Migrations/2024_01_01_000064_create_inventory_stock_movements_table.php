<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('type');
            $table->uuid('product_id')->index();
            $table->uuid('variant_id')->nullable()->index();
            $table->uuid('from_location_id')->nullable()->index();
            $table->uuid('to_location_id')->nullable()->index();
            $table->decimal('qty', 18, 8);
            $table->decimal('unit_cost', 18, 8)->default(0);
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable()->index();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('posted_by')->nullable();
            $table->timestamp('posted_at');
            $table->uuid('created_by')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_stock_movements'); }
};
