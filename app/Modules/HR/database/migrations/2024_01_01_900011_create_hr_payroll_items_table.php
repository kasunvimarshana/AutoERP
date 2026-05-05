<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('name');
            $table->string('code', 20);
            $table->string('type', 20)->default('earning');
            $table->string('calculation_type', 20)->default('fixed');
            $table->decimal('value', 20, 6)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_taxable')->default(false);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id'], 'hr_payroll_items_tenant_id_idx');
            $table->unique(['tenant_id', 'code'], 'hr_payroll_items_tenant_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_items');
    }
};
