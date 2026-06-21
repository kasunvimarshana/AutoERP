<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->char('code', 3)->unique('currencies_code_uk');
            $table->string('name', 150);
            $table->string('symbol', 16)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name'], 'currencies_active_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
