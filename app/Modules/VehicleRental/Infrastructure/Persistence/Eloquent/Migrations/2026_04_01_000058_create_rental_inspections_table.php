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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->string('inspection_type');
            $table->date('inspection_date');
            $table->string('inspector_name')->nullable();
            $table->unsignedBigInteger('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('odometer_reading')->nullable();
            $table->string('fuel_level')->nullable();
            $table->string('exterior_condition')->nullable();
            $table->string('interior_condition')->nullable();
            $table->text('damages_found')->nullable();
            $table->text('notes')->nullable();
            $table->string('overall_result')->default('pass');

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('rental_inspections'); }
};
