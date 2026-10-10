<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\IncidentFields;
use Modules\VehicleRental\Enums\IncidentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_incident_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('incident_id');
            $table->foreignId('actor_id');
            $table->string('action', IncidentFields::ACTION_LENGTH);
            $table->text('reason')->nullable();
            $table->json('snapshot');
            $table->timestamp('recorded_at');
            $table->index(['tenant_id', 'incident_id', 'id'], 'vrie_history_ix');
            $table->foreign(['incident_id', 'tenant_id'], 'vrie_incident_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_incidents')->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'vrie_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_incident_events');
    }
};
