<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_payment_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_service_payment_links_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_service_job_id');
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id')->nullable();
            $table->decimal('allocated_amount', 20, 6);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['vehicle_service_job_id', 'payment_id', 'invoice_id'], 'vehicle_service_payment_links_trace_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_payment_links_tenant_org_ix');

            $table->unique(['id', 'tenant_id'], 'vehicle_service_payment_links_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_payment_links_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_service_job_id', 'tenant_id'], 'vehicle_service_payment_links_veh_svc_job_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_jobs')
                ->cascadeOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'vehicle_service_payment_links_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'vehicle_service_payment_links_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_payment_links');
    }
};
