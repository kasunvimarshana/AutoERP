<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_invoice_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('source_line_total', 20, 6);
            $table->decimal('allocated_adjustment_total', 20, 6)->default('0.000000');
            $table->decimal('invoice_total', 20, 6);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['vehicle_service_job_id', 'invoice_id'], 'vehicle_service_invoice_links_job_invoice_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_invoice_links_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_invoice_links');
    }
};
