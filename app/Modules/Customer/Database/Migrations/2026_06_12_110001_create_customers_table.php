<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'customers_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('customer_number');
            $table->string('code');
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('customer_type');
            $table->string('status')->default('pending_approval');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies', indexName: 'customers_default_currency_fk')->nullOnDelete();
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->string('tax_registration_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('svat_number')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->boolean('is_tax_exempt')->default(false);
            $table->boolean('marketing_consent')->default(false);
            $table->string('preferred_communication_channel')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'customer_number'], 'customers_tenant_number_uk');
            $table->unique(['tenant_id', 'code'], 'customers_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'customers_tenant_org_ix');
            $table->index('customer_type', 'customers_type_ix');
            $table->index('status', 'customers_status_ix');
            $table->index('email', 'customers_email_ix');
            $table->index('phone', 'customers_phone_ix');

            $table->unique(['id', 'tenant_id'], 'customers_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'customers_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'customers_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
