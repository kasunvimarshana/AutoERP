<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->foreignId('refund_payment_id')->nullable();
            $table->string('refund_number', 100);
            $table->date('refund_date');
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->decimal('amount', 20, 6);
            $table->text('reason')->nullable();
            $table->string('status')->default('posted');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'refund_number'], 'payment_refunds_tenant_number_uk');
            $table->unique('refund_payment_id', 'payment_refunds_refund_payment_uk');
            $table->index('payment_id', 'payment_refunds_payment_idx');

            $table->unique(['id', 'tenant_id'], 'payment_refunds_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_refunds_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_refunds_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->cascadeOnDelete();
            $table->foreign(['refund_payment_id', 'tenant_id'], 'payment_refunds_refund_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();
            $table->foreign(['payment_method_id', 'tenant_id'], 'payment_refunds_payment_method_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payment_methods')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
