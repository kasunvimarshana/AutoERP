<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_agreements_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('agreement_number', 100);
            $table->string('agreement_kind', 30);
            $table->foreignId('reservation_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->date('agreement_date');
            $table->dateTime('executed_at')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('actual_ended_at')->nullable();
            $table->string('legal_context', 20)->nullable();
            $table->string('rental_mode', 30);
            $table->string('billing_cycle', 30);
            $table->string('billing_basis', 30);
            $table->string('proration_rule', 30)->default('exact_day_count');
            $table->string('billing_timezone', 60);
            $table->unsignedSmallInteger('payment_term_days')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', indexName: 'rental_agreements_currency_fk')->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->text('termination_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('terminated_by')->nullable();
            $table->dateTime('terminated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'agreement_number'], 'rental_agreements_tenant_number_uk');
            $table->unique('reservation_id', 'rental_agreements_reservation_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'agreement_kind', 'status'], 'rental_agreements_scope_kind_status_ix');
            $table->index(['customer_id', 'status', 'starts_at', 'ends_at'], 'rental_agreements_customer_period_ix');
            $table->index(['supplier_id', 'status', 'starts_at', 'ends_at'], 'rental_agreements_supplier_period_ix');

            $table->unique(['id', 'tenant_id'], 'rental_agreements_id_tenant_uk');
            $table->unique(['id', 'tenant_id', 'agreement_kind', 'customer_id'], 'rental_agreements_id_tenant_kind_customer_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_agreements_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['reservation_id', 'tenant_id'], 'rental_agreements_reservation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_reservations')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'rental_agreements_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'rental_agreements_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'rental_agreements_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['terminated_by', 'tenant_id'], 'rental_agreements_terminated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_agreements_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_agreements_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
    }
};
