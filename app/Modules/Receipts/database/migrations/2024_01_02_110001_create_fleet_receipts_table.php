<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('org_unit_id')->index();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->string('receipt_number', 64);
            $table->uuid('payment_id');
            $table->uuid('invoice_id')->nullable();
            $table->enum('receipt_type', ['payment', 'refund', 'adjustment', 'other'])->default('payment');
            $table->enum('status', ['issued', 'voided'])->default('issued');
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->string('currency', 3)->default('USD');
            $table->timestamp('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'receipt_number'], 'fleet_receipts_tenant_ou_number_uk');
            $table->index(['tenant_id', 'status'], 'fleet_receipts_tenant_status_idx');
            $table->index(['tenant_id', 'payment_id'], 'fleet_receipts_tenant_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_receipts');
    }
};
