<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('customer_id')->index();
            $table->uuid('branch_id')->index();
            $table->string('vin', 17)->nullable()->unique();
            $table->string('registration_number', 50)->unique();
            $table->string('make', 100);
            $table->string('model', 100);
            $table->integer('year')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('fuel_type', 50)->nullable();
            $table->string('transmission_type', 50)->nullable();
            $table->string('engine_number', 50)->nullable();
            $table->string('chassis_number', 50)->nullable();
            $table->integer('mileage')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('organizations')->onDelete('cascade');
            
            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('next_service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
