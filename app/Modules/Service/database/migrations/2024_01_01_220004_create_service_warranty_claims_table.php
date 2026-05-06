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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->string('warranty_provider');
            $table->string('warranty_contract_number')->nullable();
            $table->string('claim_number');
            $table->date('claim_date');
            $table->decimal('claim_amount', 20, 6);
            $table->decimal('approved_amount', 20, 6)->nullable();
            $table->decimal('rejected_amount', 20, 6)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->enum('status', ['filed','under_review','approved','rejected','paid'])->default('filed');
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id','claim_number'], 'warranty_claims_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_diagnostics');
    }
};
