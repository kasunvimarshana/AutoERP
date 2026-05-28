<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_replacements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('running_chart_id')
                ->nullable()
                ->constrained('vehicle_rental_running_charts')
                ->nullOnDelete();
            $table->foreignId('original_rental_vehicle_id')->constrained('vehicle_rental_vehicles')->restrictOnDelete();
            $table->foreignId('replacement_rental_vehicle_id')
                ->constrained('vehicle_rental_vehicles')
                ->restrictOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('replacement_number');
            $table->string('status')->default('draft');
            $table->string('reason_code');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->decimal('customer_charge_rate', 20, 4)->default(0);
            $table->decimal('provider_cost_rate', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'replacement_number'], 'vehicle_rental_replacements_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_replacements');
    }
};
