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
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_ownerships_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->string('owner_type', 40);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('owner_key', 100);
            $table->string('owner_code_snapshot', 100);
            $table->string('owner_name_snapshot');
            $table->string('ownership_type', 40);
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedTinyInteger('current_guard')->nullable();
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vehicle_ownerships_id_tenant_uk');
            $table->unique(['vehicle_id', 'owner_type', 'current_guard'], 'vehicle_owner_role_current_uk');
            $table->unique(['vehicle_id', 'owner_key', 'active_guard'], 'vehicle_owner_pair_active_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_ownerships_tenant_org_ix');
            $table->index(['tenant_id', 'owner_type', 'owner_id'], 'vehicle_ownerships_owner_ix');
            $table->index(['vehicle_id', 'started_at'], 'vehicle_ownerships_vehicle_start_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_ownerships_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vehicle_ownerships_vehicle_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_ownerships');
    }
};
