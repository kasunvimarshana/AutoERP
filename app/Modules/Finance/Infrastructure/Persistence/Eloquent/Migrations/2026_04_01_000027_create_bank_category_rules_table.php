<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_category_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('priority')->default(0);
            $table->string('match_type')->default('contains')->comment('contains, starts_with, equals, regex');
            $table->string('match_value');
            $table->json('conditions')->nullable();
            $table->foreignId('account_id')->constrained('accounts')->comment('GL account to post matched transaction');
            $table->string('description_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'bank_account_id'], 'bank_category_rules_account_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_category_rules');
    }
};
