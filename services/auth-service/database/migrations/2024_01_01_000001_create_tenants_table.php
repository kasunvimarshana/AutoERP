<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create tenants table
 *
 * Stores all registered tenants and their runtime configurations.
 * The `config` column is a JSON blob that can be hot-reloaded at runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->comment('Used for sub-domain resolution');
            $table->string('domain')->nullable()->comment('Custom domain, if any');
            $table->string('plan')->default('free')->comment('Subscription plan');
            $table->json('config')->nullable()->comment('Runtime-reloadable tenant configuration');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
