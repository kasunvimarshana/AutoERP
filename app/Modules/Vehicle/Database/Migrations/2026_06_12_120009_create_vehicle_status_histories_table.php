<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_status_histories_tenant_org_idx');
            $table->index('vehicle_id', 'vehicle_status_histories_vehicle_idx');
            $table->index('new_status', 'vehicle_status_histories_status_idx');

            $table->unique(['id', 'tenant_id'], 'vehicle_status_histories_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_status_histories_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vehicle_status_histories_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_status_histories');
    }
};
