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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'vehicle_ownerships_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->string('owner_type', 30);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('owner_scope_key', 80);
            $table->string('owner_code_snapshot', 100);
            $table->string('owner_name_snapshot', 255);
            $table->string('ownership_type', 40);
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->unsignedTinyInteger('current_guard')->nullable();
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->foreignId('supersedes_ownership_id')->nullable();
            $table->string('correction_reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vehicle_ownerships_id_tenant_uk');
            $table->unique(['tenant_id', 'vehicle_id', 'owner_type', 'current_guard'], 'vehicle_ownerships_current_uk');
            $table->unique(['tenant_id', 'vehicle_id', 'owner_scope_key', 'active_guard'], 'vehicle_ownerships_active_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'owner_type', 'owner_id'], 'vehicle_ownerships_owner_ix');
            $table->index(['tenant_id', 'vehicle_id', 'started_at', 'ended_at'], 'vehicle_ownerships_period_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_ownerships_ou_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vehicle_ownerships_vehicle_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
            $table->foreign(['supersedes_ownership_id', 'tenant_id'], 'vehicle_ownerships_previous_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_ownerships')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vehicle_ownerships_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'vehicle_ownerships_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_ownerships');
    }
};
