<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Countries
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique('countries_code_uk');
            $table->string('name');
            $table->string('phone_code', 10)->nullable();
            $table->timestamps();
        });

        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique('currencies_code_uk'); // ISO 4217
            $table->string('name');
            $table->string('symbol', 10)->nullable();
            $table->unsignedSmallInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Languages
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique('languages_code_uk');
            $table->string('name');
            $table->timestamps();
        });

        // Timezones
        Schema::create('timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique('timezones_name_uk');
            $table->string('offset', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timezones');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('countries');
    }
};
