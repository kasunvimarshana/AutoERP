<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdn_headers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference')->nullable();
            $table->foreignId('customer_id')->constrained('customers', 'id')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses', 'id')->restrictOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders', 'id')->nullOnDelete();
            $table->string('gdn_number');
            $table->string('status')->default('draft')->comment('draft, confirmed');
            $table->string('invoice_status')
                ->default('not_invoiced')
                ->comment('not_invoiced, partially_invoiced, closed');
            $table->string('picking_status')->default('pending')->comment('pending, in_progress, completed, cancelled');
            $table->string('delivery_status')
                ->default('draft')
                ->comment('draft, partially_delivered, delivered, cancelled, reversed');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->date('delivered_date');

            $table->decimal('expected_qty_total', 20, 4)->default(0);
            $table->decimal('picked_qty_total', 20, 4)->default(0);
            $table->decimal('delivered_qty_total', 20, 4)->default(0);
            $table->decimal('short_qty_total', 20, 4)->default(0);
            $table->decimal('rejected_qty_total', 20, 4)->default(0);
            $table->decimal('returned_qty_total', 20, 4)->default(0);

            // Line-derived totals - strictly SUM over lines
            $table->decimal('subtotal', 20, 4)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_tax_total', 20, 4)->default(0)->comment('SUM(line.tax_amount)');
            $table->decimal('line_discount_total', 20, 4)->default(0)->comment('SUM(line.discount_amount)');

            // Header-level adjustments applied on top of the document
            $table->string('header_discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('header_discount_value', 20, 4)->nullable();
            $table->decimal('header_discount_amount', 20, 4)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 4)->default(0);

            // Final totals combine line rollups and header adjustments
            $table->decimal('discount_total', 20, 4)
                ->default(0)
                ->comment('Application-calculated: line_discount_total + header_discount_amount');
            $table->decimal('tax_total', 20, 4)
                ->default(0)
                ->comment('Application-calculated: line_tax_total + header_tax_amount');
            $table->decimal('debit_note_total', 20, 4)->default(0)->comment('SUM of debit notes');
            $table->decimal('credit_note_total', 20, 4)->default(0)->comment('SUM of credit notes');
            $table->decimal('grand_total', 20, 4)
                ->default(0)
                ->comment(
                    'Application-calculated: subtotal - discount_total + tax_total + '
                    .'debit_note_total - credit_note_total'
                );

            $table->foreignId('tax_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('discount_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('gdn_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'gdn_number'], 'gdn_headers_gdn_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdn_headers');
    }
};
