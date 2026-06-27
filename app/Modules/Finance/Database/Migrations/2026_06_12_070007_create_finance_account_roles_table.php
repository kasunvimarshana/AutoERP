<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_account_roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_account_roles_tenant_fk')->restrictOnDelete();
            $table->string('code', 100);
            $table->string('owning_module', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'finance_account_roles_tenant_code_uk');
            $table->unique(['id', 'tenant_id'], 'finance_account_roles_id_tenant_uk');
            $table->index(['tenant_id', 'owning_module', 'is_active'], 'finance_account_roles_module_active_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_roles');
    }
};
