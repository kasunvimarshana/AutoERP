<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_dimensions_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 100);
            $table->string('name');
            $table->enum('dimension_type', [
                'organization_unit',
                'department',
                'project',
                'cost_center',
                'branch',
                'customer',
                'supplier',
                'employee',
                'vehicle',
                'custom',
            ]);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'finance_dimensions_tenant_code_uk');

            $table->unique(['id', 'tenant_id'], 'finance_dimensions_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_dimensions_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_dimensions');
    }
};
