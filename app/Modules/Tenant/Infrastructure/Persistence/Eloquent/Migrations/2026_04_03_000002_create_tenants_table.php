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
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('slug')->unique('tenants_slug_uk')->comment('URL-friendly unique name indicator');
            $table->string('logo_path')->nullable();
            $table->boolean('cross_org_transactions')->default(false);
            $table->foreignId('tenant_plan_id')->nullable()->constrained('tenant_plans', 'id')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->string('status')->default('active')->comment('active|suspended|pending|cancelled');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
