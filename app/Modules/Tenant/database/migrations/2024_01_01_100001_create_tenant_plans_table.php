<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 127)->unique('tenant_plans_slug_uq')->index('idx_tenant_plans_slug');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->enum('billing_interval', ['month', 'year'])->default('month');
            $table->boolean('is_active')->default(true)->index('idx_tenant_plans_active');
            $table->timestamps();

            $table->index(['is_active', 'billing_interval'], 'idx_tenant_plans_active_interval');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plans');
    }
};