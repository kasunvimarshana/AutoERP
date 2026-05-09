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

            ////
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants','id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units','id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('vehicle_id')->constrained('vehicles','id');
            $table->foreignId('lessee_agreement_id')->nullable()->constrained('rental_lessee_agreements','id')->nullOnDelete();
            $table->foreignId('lessor_agreement_id')->nullable()->constrained('rental_lessor_agreements','id')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees','id')->nullOnDelete();
            $table->date('run_date');
            $table->string('day_of_week')->nullable();
            $table->decimal('start_mileage',20,6)->nullable();
            $table->decimal('finish_mileage',20,6)->nullable();
            $table->decimal('km_reading',20,6)->nullable();
            $table->time('start_time')->nullable();
            $table->time('finish_time')->nullable();
            $table->decimal('working_hours',8,2)->nullable();
            $table->string('ot_type')->nullable();
            $table->text('particulars_of_hire')->nullable();
            $table->integer('night_outs')->default(0);
            $table->decimal('other_charges',20,6)->default(0);
            $table->decimal('garage_mileage',20,6)->default(0);
            $table->string('milage_option')->nullable();
            $table->string('time_option')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            ////

            ///
            $table->foreignId('tenant_id')->constrained('tenants','id')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('booking_id')->unique()->constrained('rental_bookings','id')->cascadeOnDelete();
            $table->dateTime('opened_at'); $table->dateTime('closed_at')->nullable();
            $table->decimal('base_rental_amount',20,6)->default(0);
            $table->decimal('extra_mileage_amount',20,6)->default(0);
            $table->decimal('driver_charge_amount',20,6)->default(0);
            $table->decimal('penalty_amount',20,6)->default(0);
            $table->decimal('expense_amount',20,6)->default(0);
            $table->decimal('discount_amount',20,6)->default(0);
            $table->decimal('surcharge_total',20,6)->default(0);   // debit notes sum
            $table->decimal('credit_total',20,6)->default(0);      // credit notes sum
            $table->decimal('subtotal',20,6)->default(0);
            $table->decimal('tax_amount',20,6)->default(0);
            $table->decimal('grand_total',20,6)->default(0);
            $table->enum('status',['open','closed','disputed'])->default('open');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            ///

            $table->foreignId('booking_id')->unique()->constrained('rental_bookings')->cascadeOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('base_rental_amount', 20, 6)->default(0);
            $table->decimal('extra_mileage_amount', 20, 6)->default(0);
            $table->decimal('driver_charge_amount', 20, 6)->default(0);
            $table->decimal('penalty_amount', 20, 6)->default(0);
            $table->decimal('expense_amount', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->enum('status', ['open','closed','disputed'])->default('open');
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
