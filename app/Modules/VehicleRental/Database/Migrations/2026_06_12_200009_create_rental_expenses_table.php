<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('usage_log_id')->nullable()->constrained('rental_usage_logs')->nullOnDelete();
            $table->string('expense_type', 20);
            $table->decimal('amount', 20, 6);
            $table->boolean('is_billable')->default(false);
            $table->string('receipt_no')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'rental_expenses_tenant_org_idx');
            $table->index(['agreement_id', 'expense_type', 'status'], 'rental_expenses_agreement_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_expenses');
    }
};
