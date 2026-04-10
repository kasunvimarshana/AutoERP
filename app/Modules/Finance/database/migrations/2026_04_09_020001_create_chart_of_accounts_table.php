<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense', 'contra']);
            $table->string('sub_type', 100)->nullable();
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_bank')->default(false);
            $table->boolean('is_control')->default(false);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(0);
            $table->string('path', 500);
            $table->boolean('is_leaf')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
