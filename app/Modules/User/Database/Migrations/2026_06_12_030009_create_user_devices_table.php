<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->unsignedBigInteger('organization_unit_id')->nullable()->comment('Optional organization-unit scope');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->unsignedBigInteger('user_id');
            $table->string('device_token');
            $table->string('platform')->nullable(); // ios, android, web
            $table->string('device_name')->nullable();
            $table->timestamp('last_active_at')->nullable();

            $table->timestamps();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'udev_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'udev_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'user_id', 'device_token'], 'user_devices_uk');

            $table->unique(['id', 'tenant_id'], 'user_devices_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
