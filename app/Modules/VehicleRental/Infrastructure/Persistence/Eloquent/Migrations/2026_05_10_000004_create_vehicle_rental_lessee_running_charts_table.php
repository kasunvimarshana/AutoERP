<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_lessee_running_charts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id', 'vehicle_rental_lessee_running_charts_ou_fk')->nullOnDelete();
            $table->json('metadata')->nullable();

            // $table->foreignId('vehicle_id')->constrained('vehicles', 'id');
            $table->foreignId('lessee_agreement_id')->constrained('vehicle_rental_lessee_agreements', 'id', 'vehicle_rental_lessee_running_charts_lessee_agreement_fk')->cascadeOnDelete();
            $table->foreignId('lessor_agreement_id')->nullable()->constrained('vehicle_rental_lessor_agreements', 'id', 'vehicle_rental_lessee_running_charts_lessor_agreement_fk')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees', 'id')->nullOnDelete();

            $table->date('log_date');
            // $table->decimal('start_mileage', 20, 6)->nullable();
            // $table->decimal('end_mileage', 20, 6)->nullable();
            // $table->decimal('km_reading', 20, 6)->nullable();
            $table->decimal('start_km', 20, 4)->nullable();
            $table->decimal('end_km', 20, 4)->nullable();
            $table->decimal('km_travelled', 20, 4)->nullable();
            // $table->string('day_of_week')->nullable();
            // $table->date('start_date');
            // $table->date('end_date');
            $table->time('start_time')->nullable()->comment('00:00:00');
            $table->time('end_time')->nullable()->comment('23:59:59');
            // $table->string('ot_type')->nullable();
            $table->decimal('hours_used', 20, 4)->nullable();
            $table->decimal('driver_hours_normal', 20, 4)->nullable();
            $table->decimal('driver_hours_ot', 20, 4)->nullable();
            $table->decimal('driver_hours_double_ot', 20, 4)->nullable();
            $table->integer('night_outs')->default(0);
            $table->decimal('other_charges', 20, 4)->default(0);
            $table->decimal('garage_mileage', 20, 4)->default(0);
            $table->text('particulars')->nullable();
            $table->decimal('debit_note_total', 20, 4)->default(0)->comment('SUM of debit notes');
            $table->decimal('credit_note_total', 20, 4)->default(0)->comment('SUM of credit notes');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'lessee_agreement_id', 'log_date'], 'vehicle_rental_lessee_running_charts_agreement_log_date_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_lessee_running_charts');
    }
};
