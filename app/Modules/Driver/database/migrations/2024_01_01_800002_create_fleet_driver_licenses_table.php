<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_driver_licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('fleet_drivers', 'id', 'fdl_driver_fk')->cascadeOnDelete();
            $table->string('license_number');
            $table->string('license_class');
            $table->string('issued_country', 3)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'driver_id'], 'fdl_tenant_driver_idx');
            $table->index(['tenant_id', 'expiry_date'], 'fdl_tenant_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_driver_licenses');
    }
};
