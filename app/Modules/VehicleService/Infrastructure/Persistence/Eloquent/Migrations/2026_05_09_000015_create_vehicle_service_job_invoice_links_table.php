<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_invoice_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards', 'id')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->string('invoice_type')->default('invoice');
            $table->string('direction')->default('outbound')->comment('outbound, inbound');
            $table->string('status')->default('active')->comment('active, cancelled, reversed');
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->dateTime('linked_at');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'job_card_id', 'invoice_id'], 'vehicle_service_invoice_links_unique');
            $table->index(['tenant_id', 'invoice_id'], 'vehicle_service_invoice_links_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_invoice_links');
    }
};
