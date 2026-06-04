<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->string('discount_code', 100)->nullable();
            $table->string('discount_type')->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as import keys or integration hints');

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id'], 'invoice_discounts_invoice_idx');
            $table->index(['tenant_id', 'discount_code'], 'invoice_discounts_code_idx');
            $table->index(['tenant_id', 'discount_type'], 'invoice_discounts_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_discounts');
    }
};
