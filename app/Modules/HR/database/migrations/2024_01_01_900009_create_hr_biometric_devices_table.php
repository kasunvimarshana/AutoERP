<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('name');
            $table->string('code', 50);
            $table->string('device_type', 50)->default('fingerprint');
            $table->string('ip_address', 45)->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id'], 'hr_biometric_devices_tenant_id_idx');
            $table->unique(['tenant_id', 'code'], 'hr_biometric_devices_tenant_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_biometric_devices');
    }
};
