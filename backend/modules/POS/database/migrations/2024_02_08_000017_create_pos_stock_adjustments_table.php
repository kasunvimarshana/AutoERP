<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('location_id');
            $table->string('reference_number');
            $table->timestamp('adjustment_date');
            $table->string('type'); // normal, abnormal, damaged, expired
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('pos_business_locations')->onDelete('restrict');
            $table->unique(['tenant_id', 'reference_number']);
            $table->index(['tenant_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_stock_adjustments');
    }
};
