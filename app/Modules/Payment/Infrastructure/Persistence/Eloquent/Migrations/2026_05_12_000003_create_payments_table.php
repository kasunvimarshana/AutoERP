<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('party_type')->nullable()->comment('customer, supplier, etc.');
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('payment_number');
            $table->date('payment_date');
            $table->decimal('amount', 20, 4);
            $table->decimal('allocated_amount', 20, 4)->default(0);
            $table->string('direction')->default('inbound')->comment('inbound, outbound');
            $table->foreignId('payment_group_id')->nullable()->constrained('payment_groups')->nullOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->decimal('base_amount', 20, 4);
            $table->string('status')->default('draft')->comment('draft, posted, reconciled, voided');
            $table->text('notes')->nullable();
            $table->string('idempotency_key')->nullable()->comment('prevents duplicate payments');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_of_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'payment_number'], 'payments_payment_number_uk');
            $table->unique(['tenant_id', 'idempotency_key'], 'payments_idempotency_key_uk');
            $table->index(['tenant_id', 'party_type', 'party_id'], 'payments_tenant_party_idx');
            $table->index(['tenant_id', 'status', 'payment_date'], 'payments_status_payment_date_idx');
            $table->index(['tenant_id', 'direction', 'payment_date'], 'payments_direction_payment_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
