<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_custody_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_custody_events_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('assignment_id');
            $table->string('event_type', 30);
            $table->dateTime('event_at');
            $table->decimal('odometer', 20, 6);
            $table->string('fuel_level', 50)->nullable();
            $table->text('condition_notes')->nullable();
            $table->text('damage_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vr_custody_events_id_tenant_uk');
            $table->index(['assignment_id', 'event_at'], 'vr_custody_events_assignment_time_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_custody_events_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['assignment_id', 'tenant_id'], 'vr_custody_events_assignment_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_assignments')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_custody_events_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_custody_events');
    }
};
