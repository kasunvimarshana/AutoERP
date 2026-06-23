<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_account_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('account_type_id');
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'finance_account_categories_tenant_code_uk');

            $table->unique(['id', 'tenant_id'], 'finance_account_categories_id_tenant_uk');
            $table->foreign(['account_type_id', 'tenant_id'], 'finance_account_categories_account_type_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_account_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_categories');
    }
};
