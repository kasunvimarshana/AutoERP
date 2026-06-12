<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('ownership_type');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_ownerships_tenant_org_idx');
            $table->index('vehicle_id', 'vehicle_ownerships_vehicle_idx');
            $table->index('customer_id', 'vehicle_ownerships_customer_idx');
            $table->index(['owner_type', 'owner_id'], 'vehicle_ownerships_owner_idx');
            $table->index(['vehicle_id', 'is_current'], 'vehicle_ownerships_current_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_ownerships');
    }
};
