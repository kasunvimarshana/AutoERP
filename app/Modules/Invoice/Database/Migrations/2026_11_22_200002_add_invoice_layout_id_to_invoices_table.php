<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('invoice_layout_id')->nullable()->after('currency_symbol_snapshot');

            $table->index(['tenant_id', 'invoice_layout_id'], 'invoices_tenant_layout_ix');

            $table->foreign(['invoice_layout_id', 'tenant_id'], 'invoices_invoice_layout_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoice_layouts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign('invoices_invoice_layout_id_tenant_fk');
            $table->dropIndex('invoices_tenant_layout_ix');
            $table->dropColumn('invoice_layout_id');
        });
    }
};
