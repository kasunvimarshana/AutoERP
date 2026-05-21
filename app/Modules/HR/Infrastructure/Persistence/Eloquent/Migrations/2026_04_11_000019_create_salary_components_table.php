<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->string('code');
            $table->string('type')->default('earning');
            $table->string('calculation_type')->default('fixed');
            $table->decimal('default_value', 20, 4)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('affects_net_pay')->default(true);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'salary_components_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
