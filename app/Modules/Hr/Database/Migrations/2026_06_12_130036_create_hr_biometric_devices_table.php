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
        Schema::create('hr_biometric_devices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_bio_dev_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('device_code', 32);
            $table->string('name', 100);
            $table->string('protocol', 32);
            $table->string('ip_address', 45)->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('serial_number', 64)->nullable();
            $table->string('comm_key', 64)->nullable();
            $table->string('auth_token', 128)->nullable();
            $table->string('status', 32)->default('active');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('auto_map_users')->default(true);
            $table->dateTime('last_ping_at')->nullable();
            $table->dateTime('last_sync_at')->nullable();
            $table->unsignedInteger('total_punches_synced')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_bio_dev_id_tenant_uk');
            $table->unique(['tenant_id', 'device_code'], 'hr_bio_dev_tenant_code_uk');
            $table->index(['tenant_id', 'status'], 'hr_bio_dev_tenant_status_ix');
            $table->index(['tenant_id', 'serial_number'], 'hr_bio_dev_tenant_sn_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_bio_dev_org_unit_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_biometric_devices');
    }
};
