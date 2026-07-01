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
            $table->unsignedBigInteger('tenant_id');
            $table->string('code', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'finance_account_roles_id_tenant_uk');
            $table->unique(['tenant_id', 'code'], 'finance_account_roles_tenant_code_uk');
            $table->foreign('tenant_id', 'finance_account_roles_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_roles');
    }
};
