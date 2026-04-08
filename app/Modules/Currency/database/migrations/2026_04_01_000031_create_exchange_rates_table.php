<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->char('from_currency', 3);
            $table->char('to_currency', 3);
            $table->decimal('rate', 20, 10);
            $table->date('effective_date');
            $table->string('source')->default('manual');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'from_currency', 'to_currency', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
