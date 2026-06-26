<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_departments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code'], 'hr_departments_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'hr_departments_tenant_org_ix');

            $table->unique(['id', 'tenant_id'], 'hr_departments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'hr_departments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['parent_id', 'tenant_id'], 'hr_departments_parent_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_departments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_departments');
    }
};
