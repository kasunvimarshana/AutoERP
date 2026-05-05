<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('org_unit_id')->index();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->string('invoice_number', 64);
            $table->enum('invoice_type', ['rental', 'service', 'mixed', 'other'])->default('other');
            $table->enum('entity_type', ['rental', 'service_job', 'reservation', 'other'])->default('other');
            $table->uuid('entity_id')->nullable();
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled', 'overdue'])->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6)->default('0.000000');
            $table->decimal('paid_amount', 20, 6)->default('0.000000');
            $table->decimal('balance_amount', 20, 6)->default('0.000000');
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'invoice_number'], 'fleet_invoices_tenant_ou_number_uk');
            $table->index(['tenant_id', 'status'], 'fleet_invoices_tenant_status_idx');
            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'fleet_invoices_tenant_entity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_invoices');
    }
};
