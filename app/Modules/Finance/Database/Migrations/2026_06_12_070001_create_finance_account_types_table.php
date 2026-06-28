<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_account_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_account_types_tenant_fk')->restrictOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->string('normal_balance', 40);
            $table->string('statement_type', 40);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'finance_account_types_tenant_code_uk');

            $table->unique(['id', 'tenant_id'], 'finance_account_types_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_types');
    }
};
