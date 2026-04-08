<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transaction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('rule_name');
            $table->string('match_type')->default("contains");
            $table->string('match_value');
            $table->foreignId('target_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
            $table->foreignId('target_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('target_tax_category_id')->nullable()->constrained('tax_categories')->nullOnDelete();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->index(['tenant_id', 'bank_account_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transaction_rules');
    }
};
