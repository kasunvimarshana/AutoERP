<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_incidents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->dateTime('incident_date');
            $table->string('incident_type');
            $table->enum('severity', ['minor','major','critical'])->default('minor');
            $table->text('description');
            $table->foreignId('reported_by')->constrained('users');
            $table->string('police_report_number')->nullable();
            $table->string('insurance_claim_number')->nullable();
            $table->decimal('estimated_damage_amount', 20, 6)->nullable();
            $table->decimal('actual_damage_amount', 20, 6)->nullable();
            $table->boolean('customer_responsible')->default(false);
            $table->decimal('penalty_amount', 20, 6)->nullable();
            $table->foreignId('penalty_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->enum('status', ['reported','investigating','resolved','disputed'])->default('reported');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_incidents');
    }
};
