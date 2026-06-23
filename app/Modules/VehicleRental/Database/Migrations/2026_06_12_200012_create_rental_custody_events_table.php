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
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('event_number', 100);
            $table->foreignId('vehicle_allocation_id');
            $table->foreignId('replacement_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->string('event_type', 40);
            $table->dateTime('occurred_at');
            $table->decimal('odometer', 20, 6);
            $table->decimal('fuel_level_percent', 7, 4)->nullable();
            $table->string('location')->nullable();
            $table->string('from_role', 30);
            $table->string('to_role', 30);
            $table->foreignId('handed_over_by_employee_id')->nullable();
            $table->foreignId('received_by_employee_id')->nullable();
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

            $table->unique(['id', 'tenant_id'], 'rental_custody_events_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_custody_events_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_allocation_id', 'tenant_id'], 'rental_custody_events_vehicle_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->cascadeOnDelete();
            $table->foreign(['replacement_id', 'tenant_id'], 'rental_custody_events_replacement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_replacements')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'rental_custody_events_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['handed_over_by_employee_id', 'tenant_id'], 'rental_custody_events_handed_over_by_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['received_by_employee_id', 'tenant_id'], 'rental_custody_events_received_by_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->foreign(['reversed_by', 'tenant_id'], 'rental_custody_events_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_custody_events_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_custody_events_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_custody_events');
    }
};
