<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('account_code');
            $table->string('account_name');
            $table->string('account_type');
            $table->string('account_category')->default("control");
            $table->string('normal_balance')->default("debit");
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unique(['tenant_id', 'account_code']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'account_type']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_accounts');
    }
};
