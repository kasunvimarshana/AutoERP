<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localisation_language_packs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('locale', 20)->index();
            $table->string('name');
            $table->string('direction', 3)->default('ltr');
            $table->json('strings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'locale']);
        });

        Schema::create('localisation_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique()->index();
            $table->uuid('tenant_id')->index();
            $table->string('locale', 20)->default('en');
            $table->string('timezone', 60)->default('UTC');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('number_format', 20)->default('1,234.56');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localisation_preferences');
        Schema::dropIfExists('localisation_language_packs');
    }
};
