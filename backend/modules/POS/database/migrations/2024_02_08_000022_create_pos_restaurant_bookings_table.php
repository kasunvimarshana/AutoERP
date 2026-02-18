<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_restaurant_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('location_id');
            $table->uuid('table_id');
            $table->uuid('contact_id')->nullable(); // customer
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->timestamp('booking_start');
            $table->timestamp('booking_end');
            $table->integer('number_of_guests');
            $table->string('status')->default('pending'); // pending, confirmed, seated, completed, cancelled
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('pos_business_locations')->onDelete('cascade');
            $table->foreign('table_id')->references('id')->on('pos_restaurant_tables')->onDelete('restrict');
            $table->index(['tenant_id', 'location_id', 'table_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_restaurant_bookings');
    }
};
