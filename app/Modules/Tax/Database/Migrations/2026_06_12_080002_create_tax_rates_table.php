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
            $table->foreignId('tax_id')->constrained('taxes', 'id')->cascadeOnDelete();
            $table->decimal('rate', 20, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tax_id', 'active', 'effective_from'], 'tax_rates_tax_active_from_idx');
            $table->index(['effective_from', 'effective_to'], 'tax_rates_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
