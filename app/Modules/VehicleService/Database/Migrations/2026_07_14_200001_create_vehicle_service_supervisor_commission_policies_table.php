<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_supervisor_commission_policies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vs_supervisor_commission_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->string('commission_type', 20)->default(VehicleServiceCommissionType::None->value);
            $table->decimal('commission_value', 20, 6)->default('0.000000');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['id', 'tenant_id'],
                'vs_supervisor_commission_id_tenant_uk',
            );
            $table->unique(
                ['tenant_id', 'organization_unit_id'],
                'vs_supervisor_commission_scope_uk',
            );
            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'vs_supervisor_commission_org_tenant_fk',
            )->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(
                ['created_by', 'tenant_id'],
                'vs_supervisor_commission_created_by_fk',
            )->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(
                ['updated_by', 'tenant_id'],
                'vs_supervisor_commission_updated_by_fk',
            )->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_supervisor_commission_policies');
    }
};