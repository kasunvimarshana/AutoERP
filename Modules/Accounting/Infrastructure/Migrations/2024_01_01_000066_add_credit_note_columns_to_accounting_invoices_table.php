<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounting_invoices', function (Blueprint $table) {
            $table->string('invoice_type')->default('invoice')->after('number');
            $table->uuid('source_invoice_id')->nullable()->index()->after('invoice_type');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_type', 'source_invoice_id']);
        });
    }
};
