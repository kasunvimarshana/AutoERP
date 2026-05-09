<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_lessee_running_charts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('vehicle_id')->constrained('vehicles', 'id');
            $table->foreignId('lessee_agreement_id')->nullable()->constrained('rental_lessee_agreements', 'id')->nullOnDelete();
            $table->foreignId('lessor_agreement_id')->nullable()->constrained('rental_lessor_agreements', 'id')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees', 'id')->nullOnDelete();
            // $table->string('day_of_week')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable()->comment('00:00:00');
            $table->time('end_time')->nullable()->comment('23:59:59');

            $table->decimal('start_mileage', 20, 6)->nullable();
            $table->decimal('end_mileage', 20, 6)->nullable();
            $table->decimal('km_reading', 20, 6)->nullable();

            $table->decimal('working_hours', 8, 2)->nullable();
            $table->string('ot_type')->nullable();
            $table->text('particulars_of_hire')->nullable();
            $table->integer('night_outs')->default(0);
            $table->decimal('other_charges', 20, 6)->default(0);
            $table->decimal('garage_mileage', 20, 6)->default(0);
            $table->string('milage_option')->nullable();
            $table->string('time_option')->nullable();

            $table->decimal('surcharge_total', 20, 6)->default(0)->comment('SUM of surcharge notes');
            $table->decimal('credit_total', 20, 6)->default(0)->comment('SUM of credit notes');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_lessee_running_charts');
    }
};
