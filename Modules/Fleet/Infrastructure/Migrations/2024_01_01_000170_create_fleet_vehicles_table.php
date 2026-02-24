<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('plate_number');
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('color')->nullable();
            $table->string('fuel_type')->default('petrol');
            $table->string('vin')->nullable();
            $table->uuid('assigned_to')->nullable()->index();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'plate_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicles');
    }
};
