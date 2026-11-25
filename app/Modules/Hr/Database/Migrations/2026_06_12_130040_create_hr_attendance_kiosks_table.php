<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance_kiosks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_att_kiosks_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('kiosk_code', 32);
            $table->string('name', 120);
            $table->string('secret_key', 64);
            $table->unsignedSmallInteger('rotation_seconds')->default(20);
            $table->foreignId('geofence_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_att_kiosks_id_tenant_uk');
            $table->unique(['tenant_id', 'kiosk_code'], 'hr_att_kiosks_tenant_code_uk');
            $table->index(['tenant_id', 'is_active'], 'hr_att_kiosks_tenant_active_ix');

            $table->foreign(['geofence_id', 'tenant_id'], 'hr_att_kiosks_geofence_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_attendance_geofences')
                ->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_att_kiosks_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_kiosks');
    }
};
