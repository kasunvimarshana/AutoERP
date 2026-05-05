<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'transaction_taxes_tenant_id_fk');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('tax_rate_id')->constrained('tax_rates', 'id', 'transaction_taxes_tax_rate_id_fk');
            $table->decimal('taxable_amount', 20, 6);
            $table->decimal('tax_amount', 20, 6);
            $table->foreignId('tax_account_id')->constrained('accounts', 'id', 'transaction_taxes_tax_account_id_fk');
            $table->timestamps();
            $table->index(['reference_type', 'reference_id'], 'transaction_taxes_reference_type_reference_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_taxes');
    }
};
