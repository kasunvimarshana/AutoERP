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
        Schema::create('hr_biometric_device_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_bio_dev_users_tenant_fk')->restrictOnDelete();
            $table->foreignId('device_id');
            $table->string('biometric_user_id', 64);
            $table->string('card_number', 64)->nullable();
            $table->foreignId('employee_id');
            $table->boolean('is_active')->default(true);
            $table->string('sync_status', 32)->default('synced');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_bio_dev_users_id_tenant_uk');
            $table->unique(['tenant_id', 'device_id', 'biometric_user_id'], 'hr_bio_dev_users_dev_bio_id_uk');
            $table->index(['tenant_id', 'employee_id'], 'hr_bio_dev_users_emp_ix');

            $table->foreign(['device_id', 'tenant_id'], 'hr_bio_dev_users_dev_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_biometric_devices')
                ->restrictOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'hr_bio_dev_users_emp_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_biometric_device_users');
    }
};
