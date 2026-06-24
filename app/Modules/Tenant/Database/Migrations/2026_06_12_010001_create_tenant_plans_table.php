<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->string('name');
            $table->string('slug', 100)->unique('tenant_plans_slug_uk');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_plans_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_plans_updated_by_idx');
            $table->timestamps();
            $table->index(['is_active', 'name'], 'tenant_plans_active_name_idx');
        });

        Schema::create('tenant_plan_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_plan_id')->constrained('tenant_plans', 'id')->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('features');
            $table->json('limits');
            $table->decimal('price', 20, 6)->default('0.000000');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->restrictOnDelete();
            $table->enum('billing_interval', ['month', 'quarter', 'year']);
            $table->timestamp('effective_at');
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_plan_revisions_created_by_idx');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_plan_id', 'revision_number'], 'tenant_plan_revisions_plan_number_uk');
            $table->index(['tenant_plan_id', 'effective_at'], 'tenant_plan_revisions_plan_effective_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plan_revisions');
        Schema::dropIfExists('tenant_plans');
    }
};
