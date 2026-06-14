<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('charge_calculation_id')->nullable()->constrained('rental_charge_calculations')->nullOnDelete();
            $table->string('charge_type', 30);
            $table->text('description');
            $table->decimal('quantity', 20, 6);
            $table->decimal('rate', 20, 6);
            $table->decimal('amount', 20, 6);
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6);
            $table->string('invoice_status', 30)->default('not_invoiced');
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->unique('charge_calculation_id', 'rental_charges_calculation_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_charges_tenant_org_idx');
            $table->index(['agreement_id', 'status', 'invoice_status'], 'rental_charges_agreement_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_charges');
    }
};
