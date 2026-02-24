<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('number');
            $table->uuid('partner_id')->nullable()->index();
            $table->string('partner_type')->default('customer');
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('tax_total', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->decimal('amount_paid', 18, 8)->default(0);
            $table->decimal('amount_due', 18, 8)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_invoices');
    }
};
