<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('slug')->unique('tenant_plans_slug_uk');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->decimal('price', 20, 4)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->string('billing_interval')->default('month')->comment('month|year');
            $table->boolean('is_active')->default(true)->index('tenant_plans_active_idx');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'billing_interval'], 'tenant_plans_active_interval_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plans');
    }
};
