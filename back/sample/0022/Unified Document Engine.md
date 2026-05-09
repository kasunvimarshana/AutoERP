## introducing a generic document system – a single pair of tables that handles all transaction types.

```
documents            (header – holds sales_order, shipment, invoice, return)
document_lines       (lines linked to documents)
document_references  (links between documents, e.g., invoice → order)
```

```
<!-- documents table (unified header) -->
// database/migrations/2026_05_09_000001_create_documents_table.php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();

    // Discriminator – what kind of document?
    $table->enum('doc_type', [
        'sales_order', 'purchase_order',
        'shipment', 'receipt',
        'invoice', 'credit_note',
        'return', 'quotation'
    ])->index();

    // Core identifiers
    $table->string('doc_number')->unique();
    $table->string('status')->default('draft'); // Implement per-type workflow
    $table->date('document_date');
    $table->date('due_date')->nullable();

    // Parties & references
    $table->foreignId('customer_id')->nullable()->constrained();
    $table->foreignId('vendor_id')->nullable()->constrained();
    $table->foreignId('warehouse_id')->nullable()->constrained();
    $table->foreignId('price_list_id')->nullable()->constrained();

    // Financials (exactly as you had, but once)
    $table->decimal('subtotal', 20, 6)->default(0);
    $table->decimal('line_tax_total', 20, 6)->default(0);
    $table->decimal('line_discount_total', 20, 6)->default(0);

    $table->enum('header_discount_type', ['percentage', 'fixed'])->nullable();
    $table->decimal('header_discount_value', 10, 6)->nullable();
    $table->decimal('header_discount_amount', 20, 6)->default(0);
    $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups');
    $table->decimal('header_tax_amount', 20, 6)->default(0);

    $table->decimal('discount_total', 20, 6)->default(0);
    $table->decimal('tax_total', 20, 6)->default(0);
    $table->decimal('surcharge_total', 20, 6)->default(0);
    $table->decimal('credit_total', 20, 6)->default(0);
    $table->decimal('grand_total', 20, 6)->default(0);

    $table->decimal('paid_amount', 20, 6)->default(0);
    $table->decimal('balance', 20, 6)->storedAs('grand_total - paid_amount');

    // Accounting
    $table->foreignId('ar_account_id')->nullable()->constrained('accounts');
    $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');

    // Metadata
    $table->text('notes')->nullable();
    $table->json('metadata')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');

    // Shipment specific fields (nullable)
    $table->string('carrier')->nullable();
    $table->string('tracking_number')->nullable();

    // Return specific fields (nullable)
    $table->string('return_reason')->nullable();
    $table->decimal('restocking_fee_total', 20, 6)->default(0);

    $table->timestamps();
    $table->softDeletes();

    // Indexes for fast filtering
    $table->index(['tenant_id', 'doc_type', 'status']);
    $table->index(['tenant_id', 'document_date']);
});
```

```
<!-- document_lines table (unified lines) -->
// database/migrations/2026_05_09_000002_create_document_lines_table.php
Schema::create('document_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('org_unit_id')->nullable()->constrained('org_units');

    $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();

    // Origin tracking (useful for returns/credits)
    $table->nullableMorphs('sourceable'); // e.g., sales_order_line, shipment_line

    // Product & logistics
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants');
    $table->foreignId('batch_id')->nullable()->constrained('batches');
    $table->foreignId('serial_id')->nullable()->constrained('serials');
    $table->foreignId('warehouse_id')->nullable()->constrained();
    $table->foreignId('location_id')->nullable()->constrained('warehouse_locations');
    $table->foreignId('uom_id')->constrained('units_of_measure');

    $table->text('description')->nullable();

    // Quantities
    $table->decimal('quantity', 20, 6);
    $table->decimal('unit_price', 20, 6);

    // Discount
    $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
    $table->decimal('discount_value', 10, 6)->default(0);
    $table->decimal('discount_amount', 20, 6)->default(0);

    // Taxes
    $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups');
    $table->decimal('tax_amount', 20, 6)->default(0);

    // Computed columns (stored)
    $table->decimal('gross_amount', 20, 6)->storedAs('quantity * unit_price');
    $table->decimal('line_total', 20, 6)->storedAs('gross_amount - discount_amount');
    $table->decimal('line_total_with_tax', 20, 6)->storedAs('line_total + tax_amount');

    // Return‑specific
    $table->decimal('restocking_fee', 20, 6)->default(0);
    $table->string('condition')->nullable();     // good, damaged, expired
    $table->string('disposition')->nullable();   // restock, scrap, quarantine

    // General ledger account
    $table->foreignId('account_id')->nullable()->constrained('accounts');

    $table->timestamps();
    $table->softDeletes();
});
```

```
<!-- document_references table (linking documents) -->
// database/migrations/2026_05_09_000003_create_document_references_table.php
Schema::create('document_references', function (Blueprint $table) {
    $table->id();
    $table->foreignId('source_document_id')->constrained('documents')->cascadeOnDelete();
    $table->foreignId('target_document_id')->constrained('documents')->cascadeOnDelete();
    $table->string('reference_type'); // e.g. 'invoiced_from_shipment', 'returned_from_invoice'
    $table->timestamps();
});
```
