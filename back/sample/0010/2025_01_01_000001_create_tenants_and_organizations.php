<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 0001 — Tenants (multi-tenant root)
 *
 * Every record in every table is scoped to a tenant_id.
 * Aligned to KVAutoERP's confirmed pattern: ProductData has
 * 'tenant_id' => 'required|integer|exists:tenants,id'
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('status')->default('active'); // active|suspended|trial
            $table->string('plan')->default('standard');
            $table->json('settings')->nullable();       // tenant-level config overrides
            $table->json('features')->nullable();       // feature flags
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Organizations (nested under tenant) ──────────────────────────────
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->string('fiscal_year_start', 5)->default('01-01');
            $table->json('address')->nullable();
            $table->json('contact')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('depth')->default(0);
            $table->string('path')->nullable();          // materialized path: 1/3/7
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('tenants');
    }
};
