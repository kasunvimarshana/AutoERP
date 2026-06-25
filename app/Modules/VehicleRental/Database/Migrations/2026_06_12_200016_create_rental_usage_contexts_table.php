<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_contexts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('usage_log_id')->constrained('rental_usage_logs')->cascadeOnDelete();
            $table->string('financial_side', 20);
            $table->foreignId('agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('vehicle_allocation_id')->constrained('rental_vehicle_allocations')->restrictOnDelete();
            $table->foreignId('rate_version_id')->constrained('rental_agreement_rate_versions')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->char('context_fingerprint', 64);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['usage_log_id', 'financial_side', 'agreement_id'], 'rental_usage_contexts_log_side_agreement_uk');
            $table->unique(['tenant_id', 'context_fingerprint'], 'rental_usage_contexts_fingerprint_uk');
            $table->index(['agreement_id', 'financial_side', 'usage_log_id'], 'rental_usage_contexts_agreement_side_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_contexts');
    }
};
