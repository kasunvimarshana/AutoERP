<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_models', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_make_id');
            $table->string('code');
            $table->string('name');
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'vehicle_make_id', 'code'], 'vehicle_models_tenant_make_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_models_tenant_org_idx');
            $table->index('vehicle_make_id', 'vehicle_models_make_idx');

            $table->unique(['id', 'tenant_id'], 'vehicle_models_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_models_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_make_id', 'tenant_id'], 'vehicle_models_vehicle_make_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_makes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
    }
};
