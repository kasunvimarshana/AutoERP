<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('supplier_number');
            $table->string('code');
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('supplier_type');
            $table->string('status')->default('pending_approval');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->string('tax_registration_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('svat_number')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->decimal('credit_limit', 20, 6)->default('0.000000');
            $table->decimal('outstanding_balance', 20, 6)->default('0.000000');
            $table->boolean('is_credit_allowed')->default(true);
            $table->boolean('is_advance_allowed')->default(true);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'supplier_number'], 'suppliers_tenant_number_uk');
            $table->unique(['tenant_id', 'code'], 'suppliers_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'suppliers_tenant_org_idx');
            $table->index('supplier_type', 'suppliers_type_idx');
            $table->index('status', 'suppliers_status_idx');
            $table->index('email', 'suppliers_email_idx');
            $table->index('phone', 'suppliers_phone_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
