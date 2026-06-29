<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'payment_refunds_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->foreignId('refund_payment_id');
            $table->string('refund_number', 100);
            $table->date('refund_date');
            $table->decimal('amount', 20, 6);
            $table->text('reason');
            $table->unsignedBigInteger('refunded_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'refund_number'], 'payment_refunds_tenant_number_uk');
            $table->unique('refund_payment_id', 'payment_refunds_refund_payment_uk');
            $table->index('payment_id', 'payment_refunds_payment_ix');

            $table->unique(['id', 'tenant_id'], 'payment_refunds_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_refunds_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_refunds_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();
            $table->foreign(['refund_payment_id', 'tenant_id'], 'payment_refunds_refund_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();
            $table->foreign(['refunded_by', 'tenant_id'], 'payment_refunds_refunded_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
