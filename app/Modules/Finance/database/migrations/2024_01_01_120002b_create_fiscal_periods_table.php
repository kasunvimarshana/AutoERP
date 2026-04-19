<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'fiscal_periods_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained(null, 'id', 'fiscal_periods_fiscal_year_id_fk')->cascadeOnDelete();
            $table->unsignedInteger('period_number');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'fiscal_year_id', 'period_number'], 'fiscal_periods_tenant_year_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
