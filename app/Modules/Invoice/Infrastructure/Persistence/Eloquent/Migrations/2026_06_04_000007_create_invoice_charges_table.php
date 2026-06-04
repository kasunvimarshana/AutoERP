<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_charges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->string('charge_code', 100);
            $table->string('charge_name');
            $table->string('charge_type')->default('fixed')->comment('fixed, percentage, freight, handling, service, other');
            $table->decimal('amount', 20, 4)->default(0);
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as import keys or integration hints');

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id'], 'invoice_charges_invoice_idx');
            $table->index(['tenant_id', 'charge_code'], 'invoice_charges_code_idx');
            $table->index(['tenant_id', 'charge_type'], 'invoice_charges_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_charges');
    }
};
