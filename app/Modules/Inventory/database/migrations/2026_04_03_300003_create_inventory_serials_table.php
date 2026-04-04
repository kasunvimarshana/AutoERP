<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->string('serial_number');
            $table->string('status')->default('available');
            $table->unsignedBigInteger('current_warehouse_id')->nullable();
            $table->unsignedBigInteger('current_location_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamp('warranty_expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_serials');
    }
};
