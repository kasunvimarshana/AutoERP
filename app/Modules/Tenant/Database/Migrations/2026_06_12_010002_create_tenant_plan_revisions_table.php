<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_plan_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_plan_id')->constrained('tenant_plans', 'id', indexName: 'tenant_plan_revisions_tenant_plan_fk')->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->unsignedInteger('features_schema_version');
            $table->json('features');
            $table->unsignedInteger('limits_schema_version');
            $table->json('limits');
            $table->decimal('price', 20, 6)->default('0.000000');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', indexName: 'tenant_plan_revisions_currency_fk')->restrictOnDelete();
            $table->string('billing_interval', 40);
            $table->dateTime('effective_at');
            $table->string('change_note', 1000);
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_plan_revisions_created_by_ix');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_plan_id', 'revision_number'], 'tenant_plan_revisions_plan_number_uk');
            $table->index(['tenant_plan_id', 'effective_at'], 'tenant_plan_revisions_plan_effective_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plan_revisions');
    }
};
