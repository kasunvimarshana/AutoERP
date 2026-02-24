<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('status')->default('active');
            $table->string('timezone')->default('UTC');
            $table->string('default_currency', 3)->default('USD');
            $table->string('locale', 10)->default('en');
            $table->string('logo_path')->nullable();
            $table->string('fiscal_year_start', 5)->default('01-01');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('tenants'); }
};
