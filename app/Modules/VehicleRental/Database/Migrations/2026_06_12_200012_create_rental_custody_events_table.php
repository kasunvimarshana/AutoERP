<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_custody_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->string('event_number', 100);
            $table->foreignId('vehicle_allocation_id')->constrained('rental_vehicle_allocations')->cascadeOnDelete();
            $table->foreignId('replacement_id')->nullable()->constrained('rental_vehicle_replacements')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('event_type', 40);
            $table->dateTime('occurred_at');
            $table->decimal('odometer', 20, 6);
            $table->decimal('fuel_level_percent', 7, 4)->nullable();
            $table->string('location')->nullable();
            $table->string('from_role', 30);
            $table->string('to_role', 30);
            $table->foreignId('handed_over_by_employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->foreignId('received_by_employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->string('external_handed_over_name', 150)->nullable();
            $table->string('external_received_by_name', 150)->nullable();
            $table->text('condition_summary')->nullable();
            $table->text('damage_summary')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'event_number'], 'rental_custody_events_tenant_number_uk');
            $table->index(['vehicle_id', 'occurred_at'], 'rental_custody_events_vehicle_at_idx');
            $table->index(['vehicle_allocation_id', 'event_type', 'occurred_at'], 'rental_custody_events_allocation_type_idx');
            $table->index(['status', 'occurred_at'], 'rental_custody_events_status_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_custody_events');
    }
};
