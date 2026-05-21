<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type')->comment('asset, liability, equity, income, expense');
            $table->string('normal_balance')->default('debit')->comment('debit, credit');
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_cash_account')->default(false);
            $table->boolean('is_system')->default(false);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allows_manual_posting')->default(true);
            $table->string('path')->nullable()->comment('materialised path');
            $table->unsignedInteger('depth')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'accounts_code_uk');
            $table->index(['tenant_id', 'type'], 'accounts_type_idx');
            $table->index(['tenant_id', 'parent_id'], 'accounts_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
