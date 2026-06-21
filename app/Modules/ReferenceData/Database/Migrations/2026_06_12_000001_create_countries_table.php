<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->char('code', 2)->unique('countries_code_uk');
            $table->string('name', 150);
            $table->string('phone_code', 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name'], 'countries_active_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
