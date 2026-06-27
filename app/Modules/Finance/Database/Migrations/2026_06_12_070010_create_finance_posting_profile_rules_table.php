<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_posting_profile_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_posting_profile_rules_tenant_fk')->restrictOnDelete();
            $table->foreignId('posting_profile_id');
            $table->string('line_key', 100);
            $table->foreignId('account_role_id');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'finance_posting_rules_id_tenant_uk');
            $table->unique(['posting_profile_id', 'line_key'], 'finance_posting_profile_rules_profile_key_uk');
            $table->foreign(['posting_profile_id', 'tenant_id'], 'finance_posting_rules_profile_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_posting_profiles')
                ->restrictOnDelete();
            $table->foreign(['account_role_id', 'tenant_id'], 'finance_posting_rules_role_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_account_roles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_posting_profile_rules');
    }
};
