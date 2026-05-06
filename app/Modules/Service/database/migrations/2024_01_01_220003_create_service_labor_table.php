<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_labor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('employees');
            $table->string('labor_type')->default('standard'); // standard, overtime, weekend, emergency
            $table->decimal('hours_worked', 8, 2);
            $table->decimal('hourly_rate', 20, 6);
            $table->decimal('total_amount', 20, 6);
            $table->decimal('commission_percentage', 7, 4)->nullable();
            $table->decimal('commission_amount', 20, 6)->nullable();
            $table->decimal('incentive_amount', 20, 6)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_labor');
    }
};
