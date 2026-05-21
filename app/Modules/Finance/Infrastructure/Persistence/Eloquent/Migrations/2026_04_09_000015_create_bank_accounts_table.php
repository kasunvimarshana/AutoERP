<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('routing_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_bic')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->comment('linked Bank GL account');
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->decimal('last_reconciled_balance', 20, 4)->nullable();
            $table->string('feed_provider')->nullable()->comment('bank feed: plaid, yodlee, csv');
            $table->text('feed_credentials_enc')->nullable()->comment('encrypted credentials');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'account_number'], 'bank_accounts_account_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
