<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_reservation_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('reservation_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();

            $table->index(['reservation_id', 'changed_at'], 'vr_res_history_res_time_ix');

            $table->unique(['id', 'tenant_id'], 'vr_res_history_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_res_history_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['reservation_id', 'tenant_id'], 'vr_res_history_reservation_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_reservations')
                ->cascadeOnDelete();
            $table->foreign(['changed_by', 'tenant_id'], 'vr_res_history_changed_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_reservation_histories');
    }
};
