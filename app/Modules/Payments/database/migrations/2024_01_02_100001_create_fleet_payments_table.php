<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('org_unit_id')->index();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->string('payment_number', 64);
            $table->uuid('invoice_id');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'wallet', 'other'])->default('cash');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->string('currency', 3)->default('USD');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference_number', 128)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'payment_number'], 'fleet_payments_tenant_ou_number_uk');
            $table->index(['tenant_id', 'status'], 'fleet_payments_tenant_status_idx');
            $table->index(['tenant_id', 'invoice_id'], 'fleet_payments_tenant_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_payments');
    }
};
