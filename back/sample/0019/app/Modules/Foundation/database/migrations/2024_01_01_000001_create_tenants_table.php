<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('logo_path')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->string('currency_code', 10)->default('USD');
            $table->string('date_format')->default('Y-m-d');
            $table->string('time_format')->default('H:i:s');
            $table->string('number_format')->default('1,234.56');
            $table->enum('status', ['active', 'inactive', 'suspended', 'trial'])->default('active');
            $table->json('settings')->nullable();
            $table->json('features')->nullable()->comment('Enabled feature flags');
            $table->string('plan')->default('standard');
            $table->timestamp('plan_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
