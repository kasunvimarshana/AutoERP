<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'tax_rates_tenant_fk')->restrictOnDelete();
            $table->foreignId('tax_id');
            $table->decimal('rate', 20, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'tax_rates_id_tenant_uk');
            $table->foreign(['tax_id', 'tenant_id'], 'tax_rates_tax_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('taxes')
                ->cascadeOnDelete();
            $table->index(['tenant_id', 'tax_id', 'active', 'effective_from'], 'tax_rates_tax_active_from_ix');
            $table->index(['effective_from', 'effective_to'], 'tax_rates_dates_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
