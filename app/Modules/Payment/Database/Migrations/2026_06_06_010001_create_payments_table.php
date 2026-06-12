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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('payment_number', 100);
            $table->enum('payment_type', [
                'supplier_payment',
                'customer_receipt',
                'service_receipt',
                'rental_receipt',
                'advance',
                'refund',
                'manual',
            ]);
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->date('payment_date');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->string('reference_number')->nullable();
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'posted',
                'partially_allocated',
                'fully_allocated',
                'allocated',
                'refunded',
                'void',
                'reversed',
                'cancelled',
            ])->default('draft');
            $table->decimal('total_amount', 20, 6)->default('0');
            $table->decimal('allocated_amount', 20, 6)->default('0');
            $table->decimal('unapplied_amount', 20, 6)->default('0');
            $table->decimal('refunded_amount', 20, 6)->default('0');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'payment_number'], 'payments_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'payments_tenant_org_idx');
            $table->index(['payment_type', 'direction', 'status'], 'payments_type_direction_status_idx');
            $table->index(['party_type', 'party_id'], 'payments_party_idx');
            $table->index('payment_date', 'payments_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
