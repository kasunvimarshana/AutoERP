<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->uuid('uuid')->unique('tenants_uuid_uk');
            $table->string('code', 100)->unique('tenants_code_uk');
            $table->string('name');
            $table->string('slug')->unique('tenants_slug_uk')->comment('URL-friendly unique name indicator');
            $table->string('logo_path')->nullable();
            $table->boolean('cross_org_transactions')->default(false);
            $table->foreignId('tenant_plan_id')->nullable()->constrained('tenant_plans', 'id')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->string('status')->default('active')->comment('active|inactive|suspended');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_isolated')->default(true);
            $table->string('isolation_key', 255)->nullable()->unique('tenants_isolation_key_uk');
            $table->string('configuration_scope', 255)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active'], 'tenants_status_active_idx');
            $table->index('configuration_scope', 'tenants_configuration_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
