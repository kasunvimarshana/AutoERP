<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Constants\ConfigurationValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_unit_configuration_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id');
            $table->string('key', 191);
            $table->unsignedInteger('definition_version');
            $table->longText('value')->nullable();
            $table->enum('value_type', ConfigurationValueType::values());
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();

            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'organization_configuration_values_org_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->cascadeOnDelete();
            $table->unique(
                ['tenant_id', 'organization_unit_id', 'key'],
                'organization_configuration_values_scope_key_uk',
            );
            $table->index(
                ['tenant_id', 'organization_unit_id', 'updated_at'],
                'organization_configuration_values_scope_updated_idx',
            );

            $table->unique(['id', 'tenant_id'], 'organization_unit_configuration_values_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_configuration_values');
    }
};
