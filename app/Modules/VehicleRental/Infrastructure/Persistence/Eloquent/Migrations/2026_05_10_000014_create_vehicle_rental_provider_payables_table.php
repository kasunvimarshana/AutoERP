<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_provider_payables', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('provider_party_type')->nullable()->comment('supplier, customer, company, external_party');
            $table->unsignedBigInteger('provider_party_id')->nullable();
            $table->string('provider_party_name')->nullable();
            $table->foreignId('rental_vehicle_id')->nullable()->constrained('vehicle_rental_vehicles')->nullOnDelete();
            $table->foreignId('replacement_id')->nullable()->constrained('vehicle_rental_replacements')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('payable_number');
            $table->string('source_entity_type');
            $table->unsignedBigInteger('source_entity_id');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('finance_status')->default('draft');
            $table->date('payable_date');
            $table->date('due_date')->nullable();
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_total', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->decimal('paid_total', 20, 4)->default(0);
            $table->decimal('balance', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'payable_number'], 'vehicle_rental_provider_payables_number_uk');
            $table->index(['tenant_id', 'provider_id'], 'vehicle_rental_provider_payables_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_provider_payables');
    }
};
