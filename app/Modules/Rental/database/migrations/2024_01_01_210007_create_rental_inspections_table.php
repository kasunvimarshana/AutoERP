<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->enum('inspection_type', ['pre_rental','post_rental']);
            $table->foreignId('inspected_by')->constrained('users');
            $table->dateTime('inspection_date');
            $table->enum('overall_condition', ['excellent','good','fair','poor'])->nullable();
            $table->decimal('fuel_level', 5, 2)->nullable();
            $table->unsignedBigInteger('odometer_reading')->nullable();
            $table->text('notes')->nullable();
            $table->json('damage_report')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_inspections');
    }
};
