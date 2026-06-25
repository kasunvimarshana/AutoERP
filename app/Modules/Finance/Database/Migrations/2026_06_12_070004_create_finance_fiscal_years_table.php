<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('open');
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'start_date', 'end_date'],
                'finance_fiscal_years_scope_dates_uk',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fiscal_years');
    }
};
