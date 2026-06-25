<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->string('attribute_key');
            $table->text('attribute_value')->nullable();
            $table->string('data_type')->default('text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_attributes_tenant_org_idx');
            $table->index('vehicle_id', 'vehicle_attributes_vehicle_idx');
            $table->unique(['vehicle_id', 'attribute_key'], 'vehicle_attributes_vehicle_key_uk');

            $table->unique(['id', 'tenant_id'], 'vehicle_attributes_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_attributes_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vehicle_attributes_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_attributes');
    }
};
