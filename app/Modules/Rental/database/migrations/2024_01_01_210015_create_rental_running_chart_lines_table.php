<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_running_chart_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('running_chart_id')->constrained('rental_running_charts')->cascadeOnDelete();
            $table->integer('line_number');
            $table->string('charge_type');
            $table->string('description');
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('unit_price', 20, 6)->nullable();
            $table->decimal('amount', 20, 6);
            $table->boolean('is_taxable')->default(false);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('debit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('credit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','running_chart_id','line_number'], 'chart_lines_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_running_chart_lines');
    }
};
