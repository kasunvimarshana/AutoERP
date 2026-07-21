<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_document_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'invoice_doc_snapshots_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('invoice_id');
            $table->string('document_kind', 50);
            $table->boolean('organization_profile_present')->default(false);

            foreach (['seller', 'buyer'] as $role) {
                $table->string($role.'_legal_name');
                $table->string($role.'_tin', 150)->nullable();
                $table->string($role.'_vat_registration_number', 150)->nullable();
                $table->string($role.'_svat_registration_number', 150)->nullable();
                $table->text($role.'_address')->nullable();
                $table->string($role.'_phone', 100)->nullable();
                $table->string($role.'_email')->nullable();
            }

            $table->date('supply_date')->nullable();
            $table->date('supply_period_start')->nullable();
            $table->date('supply_period_end')->nullable();
            $table->text('place_of_supply')->nullable();
            $table->string('payment_mode', 100)->nullable();
            $table->string('payment_terms')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_id'], 'invoice_doc_snapshots_tenant_invoice_uk');
            $table->unique(['id', 'tenant_id'], 'invoice_doc_snapshots_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'invoice_doc_snapshots_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'invoice_doc_snapshots_invoice_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_document_snapshots');
    }
};
