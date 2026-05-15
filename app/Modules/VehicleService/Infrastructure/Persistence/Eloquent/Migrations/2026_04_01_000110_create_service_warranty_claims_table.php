<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->string('warranty_provider');
            $table->string('warranty_contract_number')->nullable();
            $table->string('claim_number');
            $table->date('claim_date');
            $table->decimal('claim_amount', 20, 4);
            $table->decimal('approved_amount', 20, 4)->nullable();
            $table->decimal('rejected_amount', 20, 4)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('status')->default('filed');
            $table->foreignId('receivable_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'claim_number'], 'service_warranty_claims_claim_number_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'job_card_id'], 'service_warranty_claims_job_card_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_warranty_claims');
    }
};
