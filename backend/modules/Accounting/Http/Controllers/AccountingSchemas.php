<?php

namespace Modules\Accounting\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="Account",
 *     type="object",
 *     title="Account",
 *     description="Chart of accounts entry representing an accounting account",
 *     required={"id", "code", "name", "type"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Unique account identifier"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Tenant identifier"),
 *     @OA\Property(property="parent_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440002", description="Parent account ID for hierarchical structure"),
 *     @OA\Property(property="code", type="string", maxLength=50, example="1000", description="Unique account code"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Cash", description="Account name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Primary operating cash account", description="Account description"),
 *     @OA\Property(property="type", type="string", enum={"asset", "liability", "equity", "revenue", "expense"}, example="asset", description="Account type based on fundamental accounting equation"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code for multi-currency support"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether account is active and can be used in transactions"),
 *     @OA\Property(property="is_system", type="boolean", example=false, description="Whether this is a system account that cannot be deleted"),
 *     @OA\Property(property="balance", type="number", format="decimal", example=50000.00, description="Current account balance"),
 *     @OA\Property(property="debit_total", type="number", format="decimal", example=75000.00, description="Total debit amount"),
 *     @OA\Property(property="credit_total", type="number", format="decimal", example=25000.00, description="Total credit amount"),
 *     @OA\Property(
 *         property="children",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(ref="#/components/schemas/Account"),
 *         description="Child accounts in hierarchical structure"
 *     ),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="JournalEntry",
 *     type="object",
 *     title="Journal Entry",
 *     description="Double-entry accounting journal entry with debit and credit lines",
 *     required={"id", "entry_number", "entry_date", "reference"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010", description="Unique journal entry identifier"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Tenant identifier"),
 *     @OA\Property(property="entry_number", type="string", example="JE-2024-001", description="Unique journal entry number"),
 *     @OA\Property(property="entry_date", type="string", format="date", example="2024-01-15", description="Transaction date"),
 *     @OA\Property(property="reference", type="string", maxLength=255, example="INV-2024-001", description="External reference or source document number"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Recording revenue from invoice INV-2024-001", description="Journal entry description"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Transaction currency code"),
 *     @OA\Property(property="is_posted", type="boolean", example=false, description="Whether entry is posted to general ledger"),
 *     @OA\Property(property="posted_at", type="string", format="date-time", nullable=true, example="2024-01-15T14:30:00Z", description="Timestamp when entry was posted"),
 *     @OA\Property(property="posted_by", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440020", description="User who posted the entry"),
 *     @OA\Property(property="total_debit", type="number", format="decimal", example=1000.00, description="Total debit amount (must equal total_credit)"),
 *     @OA\Property(property="total_credit", type="number", format="decimal", example=1000.00, description="Total credit amount (must equal total_debit)"),
 *     @OA\Property(property="is_balanced", type="boolean", example=true, description="Whether debits equal credits"),
 *     @OA\Property(
 *         property="lines",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/JournalEntryLine"),
 *         description="Journal entry lines (minimum 2 required for double-entry)"
 *     ),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="JournalEntryLine",
 *     type="object",
 *     title="Journal Entry Line",
 *     description="Individual line item in a journal entry",
 *     required={"id", "journal_entry_id", "account_id", "debit_amount", "credit_amount"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440030", description="Unique line identifier"),
 *     @OA\Property(property="journal_entry_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010", description="Journal entry identifier"),
 *     @OA\Property(property="account_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Account identifier"),
 *     @OA\Property(property="account_code", type="string", example="1000", description="Account code (for reference)"),
 *     @OA\Property(property="account_name", type="string", example="Cash", description="Account name (for reference)"),
 *     @OA\Property(property="debit_amount", type="number", format="decimal", example=1000.00, description="Debit amount (0 if credit entry)"),
 *     @OA\Property(property="credit_amount", type="number", format="decimal", example=0.00, description="Credit amount (0 if debit entry)"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Payment received from customer", description="Line item description"),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Invoice",
 *     type="object",
 *     title="Invoice",
 *     description="Customer invoice for accounts receivable management",
 *     required={"id", "invoice_number", "customer_id", "invoice_date", "due_date", "status"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040", description="Unique invoice identifier"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Tenant identifier"),
 *     @OA\Property(property="invoice_number", type="string", example="INV-2024-001", description="Unique invoice number"),
 *     @OA\Property(property="sales_order_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440050", description="Related sales order ID"),
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060", description="Customer identifier"),
 *     @OA\Property(property="customer_name", type="string", example="Acme Corporation", description="Customer name (snapshot)"),
 *     @OA\Property(property="invoice_date", type="string", format="date", example="2024-01-15", description="Invoice date"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-02-14", description="Payment due date"),
 *     @OA\Property(property="status", type="string", enum={"draft", "sent", "paid", "overdue", "cancelled", "partial"}, example="sent", description="Invoice status"),
 *     @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main St, New York, NY 10001", description="Customer billing address"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Invoice currency"),
 *     @OA\Property(property="exchange_rate", type="number", format="decimal", example=1.000000, description="Exchange rate to base currency"),
 *     @OA\Property(property="subtotal", type="number", format="decimal", example=10000.00, description="Invoice subtotal before tax"),
 *     @OA\Property(property="tax_amount", type="number", format="decimal", example=800.00, description="Total tax amount"),
 *     @OA\Property(property="discount_amount", type="number", format="decimal", example=0.00, description="Total discount amount"),
 *     @OA\Property(property="total_amount", type="number", format="decimal", example=10800.00, description="Invoice total amount"),
 *     @OA\Property(property="amount_paid", type="number", format="decimal", example=5000.00, description="Amount paid so far"),
 *     @OA\Property(property="amount_due", type="number", format="decimal", example=5800.00, description="Remaining amount due"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Thank you for your business", description="Invoice notes"),
 *     @OA\Property(property="terms", type="string", nullable=true, example="Payment due within 30 days", description="Payment terms"),
 *     @OA\Property(property="sent_at", type="string", format="date-time", nullable=true, example="2024-01-15T15:00:00Z", description="When invoice was sent to customer"),
 *     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true, example="2024-02-10T09:30:00Z", description="When invoice was fully paid"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/InvoiceItem"),
 *         description="Invoice line items"
 *     ),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="InvoiceItem",
 *     type="object",
 *     title="Invoice Item",
 *     description="Line item on an invoice",
 *     required={"id", "invoice_id", "description", "quantity", "unit_price"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440070", description="Unique item identifier"),
 *     @OA\Property(property="invoice_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040", description="Invoice identifier"),
 *     @OA\Property(property="product_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440080", description="Product identifier"),
 *     @OA\Property(property="description", type="string", example="Professional services - 40 hours", description="Item description"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=40.00, description="Quantity"),
 *     @OA\Property(property="unit_price", type="number", format="decimal", example=150.00, description="Price per unit"),
 *     @OA\Property(property="tax_rate", type="number", format="decimal", nullable=true, example=8.00, description="Tax rate percentage"),
 *     @OA\Property(property="tax_amount", type="number", format="decimal", example=480.00, description="Calculated tax amount"),
 *     @OA\Property(property="discount_amount", type="number", format="decimal", example=0.00, description="Line discount amount"),
 *     @OA\Property(property="line_total", type="number", format="decimal", example=6480.00, description="Line total (quantity * unit_price + tax - discount)"),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Payment",
 *     description="Customer payment for accounts receivable",
 *     required={"id", "payment_number", "customer_id", "payment_date", "amount", "payment_method"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090", description="Unique payment identifier"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Tenant identifier"),
 *     @OA\Property(property="payment_number", type="string", example="PAY-2024-001", description="Unique payment number"),
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060", description="Customer identifier"),
 *     @OA\Property(property="customer_name", type="string", example="Acme Corporation", description="Customer name (snapshot)"),
 *     @OA\Property(property="payment_method", type="string", enum={"cash", "bank_transfer", "credit_card", "debit_card", "check", "online", "mobile", "other"}, example="bank_transfer", description="Payment method"),
 *     @OA\Property(property="payment_date", type="string", format="date", example="2024-02-10", description="Payment date"),
 *     @OA\Property(property="amount", type="number", format="decimal", example=5000.00, description="Payment amount"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Payment currency"),
 *     @OA\Property(property="exchange_rate", type="number", format="decimal", example=1.000000, description="Exchange rate to base currency"),
 *     @OA\Property(property="reference", type="string", nullable=true, maxLength=255, example="TXN-12345678", description="External payment reference or transaction ID"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "refunded", "cancelled"}, example="completed", description="Payment status"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Partial payment for invoice INV-2024-001", description="Payment notes"),
 *     @OA\Property(property="amount_allocated", type="number", format="decimal", example=5000.00, description="Amount allocated to invoices"),
 *     @OA\Property(property="amount_unallocated", type="number", format="decimal", example=0.00, description="Unallocated amount (credit on account)"),
 *     @OA\Property(
 *         property="allocations",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PaymentAllocation"),
 *         description="Payment allocations to invoices"
 *     ),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-02-10T09:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-02-10T09:30:00Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PaymentAllocation",
 *     type="object",
 *     title="Payment Allocation",
 *     description="Allocation of payment amount to specific invoice",
 *     required={"id", "payment_id", "invoice_id", "amount"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440100", description="Unique allocation identifier"),
 *     @OA\Property(property="payment_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090", description="Payment identifier"),
 *     @OA\Property(property="invoice_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040", description="Invoice identifier"),
 *     @OA\Property(property="invoice_number", type="string", example="INV-2024-001", description="Invoice number (for reference)"),
 *     @OA\Property(property="amount", type="number", format="decimal", example=5000.00, description="Amount allocated to this invoice"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Partial payment", description="Allocation notes"),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-02-10T09:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-02-10T09:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StoreAccountRequest",
 *     type="object",
 *     title="Store Account Request",
 *     description="Request body for creating a new account",
 *     required={"code", "name", "type"},
 *     @OA\Property(property="parent_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440002", description="Parent account ID"),
 *     @OA\Property(property="code", type="string", maxLength=50, example="1000", description="Unique account code"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Cash", description="Account name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Primary operating cash account", description="Account description"),
 *     @OA\Property(property="type", type="string", enum={"asset", "liability", "equity", "revenue", "expense"}, example="asset", description="Account type"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether account is active")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateAccountRequest",
 *     type="object",
 *     title="Update Account Request",
 *     description="Request body for updating an existing account",
 *     @OA\Property(property="parent_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440002", description="Parent account ID"),
 *     @OA\Property(property="code", type="string", maxLength=50, example="1000", description="Account code"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Cash", description="Account name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Primary operating cash account", description="Account description"),
 *     @OA\Property(property="type", type="string", enum={"asset", "liability", "equity", "revenue", "expense"}, example="asset", description="Account type"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether account is active")
 * )
 *
 * @OA\Schema(
 *     schema="StoreJournalEntryRequest",
 *     type="object",
 *     title="Store Journal Entry Request",
 *     description="Request body for creating a new journal entry",
 *     required={"entry_date", "reference", "lines"},
 *     @OA\Property(property="entry_date", type="string", format="date", example="2024-01-15", description="Transaction date"),
 *     @OA\Property(property="reference", type="string", maxLength=255, example="INV-2024-001", description="External reference"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Recording revenue from invoice", description="Entry description"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code"),
 *     @OA\Property(property="is_posted", type="boolean", example=false, description="Whether to post immediately"),
 *     @OA\Property(
 *         property="lines",
 *         type="array",
 *         minItems=2,
 *         description="Journal entry lines (minimum 2 required)",
 *         @OA\Items(
 *             type="object",
 *             required={"account_id", "debit_amount", "credit_amount"},
 *             @OA\Property(property="account_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Account ID"),
 *             @OA\Property(property="debit_amount", type="number", format="decimal", example=1000.00, description="Debit amount (0 if credit entry)"),
 *             @OA\Property(property="credit_amount", type="number", format="decimal", example=0.00, description="Credit amount (0 if debit entry)"),
 *             @OA\Property(property="description", type="string", nullable=true, example="Payment received", description="Line description")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateJournalEntryRequest",
 *     type="object",
 *     title="Update Journal Entry Request",
 *     description="Request body for updating an existing journal entry (only if not posted)",
 *     @OA\Property(property="entry_date", type="string", format="date", example="2024-01-15", description="Transaction date"),
 *     @OA\Property(property="reference", type="string", maxLength=255, example="INV-2024-001", description="External reference"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Recording revenue from invoice", description="Entry description"),
 *     @OA\Property(
 *         property="lines",
 *         type="array",
 *         minItems=2,
 *         description="Journal entry lines (minimum 2 required)",
 *         @OA\Items(
 *             type="object",
 *             required={"account_id", "debit_amount", "credit_amount"},
 *             @OA\Property(property="account_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Account ID"),
 *             @OA\Property(property="debit_amount", type="number", format="decimal", example=1000.00, description="Debit amount"),
 *             @OA\Property(property="credit_amount", type="number", format="decimal", example=0.00, description="Credit amount"),
 *             @OA\Property(property="description", type="string", nullable=true, example="Payment received", description="Line description")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StoreInvoiceRequest",
 *     type="object",
 *     title="Store Invoice Request",
 *     description="Request body for creating a new invoice",
 *     required={"customer_id", "invoice_date", "due_date", "items"},
 *     @OA\Property(property="sales_order_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440050", description="Related sales order ID"),
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060", description="Customer ID"),
 *     @OA\Property(property="invoice_date", type="string", format="date", example="2024-01-15", description="Invoice date"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-02-14", description="Payment due date (must be on or after invoice_date)"),
 *     @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main St, New York, NY 10001", description="Billing address"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Thank you for your business", description="Invoice notes"),
 *     @OA\Property(property="terms", type="string", nullable=true, example="Payment due within 30 days", description="Payment terms"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         minItems=1,
 *         description="Invoice line items",
 *         @OA\Items(
 *             type="object",
 *             required={"description", "quantity", "unit_price"},
 *             @OA\Property(property="product_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440080", description="Product ID"),
 *             @OA\Property(property="description", type="string", example="Professional services - 40 hours", description="Item description"),
 *             @OA\Property(property="quantity", type="number", format="decimal", minimum=0.01, example=40.00, description="Quantity"),
 *             @OA\Property(property="unit_price", type="number", format="decimal", minimum=0, example=150.00, description="Price per unit"),
 *             @OA\Property(property="tax_rate", type="number", format="decimal", nullable=true, minimum=0, maximum=100, example=8.00, description="Tax rate percentage"),
 *             @OA\Property(property="discount_amount", type="number", format="decimal", nullable=true, minimum=0, example=0.00, description="Discount amount")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateInvoiceRequest",
 *     type="object",
 *     title="Update Invoice Request",
 *     description="Request body for updating an existing invoice",
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060", description="Customer ID"),
 *     @OA\Property(property="status", type="string", enum={"draft", "sent", "paid", "overdue", "cancelled", "partial"}, example="sent", description="Invoice status"),
 *     @OA\Property(property="invoice_date", type="string", format="date", example="2024-01-15", description="Invoice date"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-02-14", description="Payment due date"),
 *     @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main St, New York, NY 10001", description="Billing address"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Thank you for your business", description="Invoice notes"),
 *     @OA\Property(property="terms", type="string", nullable=true, example="Payment due within 30 days", description="Payment terms"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         minItems=1,
 *         description="Invoice line items",
 *         @OA\Items(
 *             type="object",
 *             required={"description", "quantity", "unit_price"},
 *             @OA\Property(property="product_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440080", description="Product ID"),
 *             @OA\Property(property="description", type="string", example="Professional services - 40 hours", description="Item description"),
 *             @OA\Property(property="quantity", type="number", format="decimal", minimum=0.01, example=40.00, description="Quantity"),
 *             @OA\Property(property="unit_price", type="number", format="decimal", minimum=0, example=150.00, description="Price per unit"),
 *             @OA\Property(property="tax_rate", type="number", format="decimal", nullable=true, minimum=0, maximum=100, example=8.00, description="Tax rate percentage"),
 *             @OA\Property(property="discount_amount", type="number", format="decimal", nullable=true, minimum=0, example=0.00, description="Discount amount")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StorePaymentRequest",
 *     type="object",
 *     title="Store Payment Request",
 *     description="Request body for recording a new payment",
 *     required={"customer_id", "payment_method", "payment_date", "amount"},
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060", description="Customer ID"),
 *     @OA\Property(property="payment_method", type="string", enum={"cash", "bank_transfer", "credit_card", "debit_card", "check", "online", "mobile", "other"}, example="bank_transfer", description="Payment method"),
 *     @OA\Property(property="payment_date", type="string", format="date", example="2024-02-10", description="Payment date"),
 *     @OA\Property(property="amount", type="number", format="decimal", minimum=0.01, example=5000.00, description="Payment amount"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code"),
 *     @OA\Property(property="reference", type="string", nullable=true, maxLength=255, example="TXN-12345678", description="External reference or transaction ID"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Partial payment", description="Payment notes"),
 *     @OA\Property(
 *         property="allocations",
 *         type="array",
 *         nullable=true,
 *         description="Optional allocations to specific invoices",
 *         @OA\Items(
 *             type="object",
 *             required={"invoice_id", "amount"},
 *             @OA\Property(property="invoice_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040", description="Invoice ID"),
 *             @OA\Property(property="amount", type="number", format="decimal", minimum=0.01, example=5000.00, description="Amount to allocate"),
 *             @OA\Property(property="notes", type="string", nullable=true, example="Partial payment", description="Allocation notes")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdatePaymentRequest",
 *     type="object",
 *     title="Update Payment Request",
 *     description="Request body for updating an existing payment",
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060", description="Customer ID"),
 *     @OA\Property(property="payment_method", type="string", enum={"cash", "bank_transfer", "credit_card", "debit_card", "check", "online", "mobile", "other"}, example="bank_transfer", description="Payment method"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "refunded", "cancelled"}, example="completed", description="Payment status"),
 *     @OA\Property(property="payment_date", type="string", format="date", example="2024-02-10", description="Payment date"),
 *     @OA\Property(property="amount", type="number", format="decimal", minimum=0.01, example=5000.00, description="Payment amount"),
 *     @OA\Property(property="currency_code", type="string", nullable=true, maxLength=3, example="USD", description="Currency code"),
 *     @OA\Property(property="reference", type="string", nullable=true, maxLength=255, example="TXN-12345678", description="External reference"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Partial payment", description="Payment notes")
 * )
 *
 * @OA\Schema(
 *     schema="AllocatePaymentRequest",
 *     type="object",
 *     title="Allocate Payment Request",
 *     description="Request body for allocating payment to invoices",
 *     required={"allocations"},
 *     @OA\Property(
 *         property="allocations",
 *         type="array",
 *         minItems=1,
 *         description="Payment allocations to invoices",
 *         @OA\Items(
 *             type="object",
 *             required={"invoice_id", "amount"},
 *             @OA\Property(property="invoice_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040", description="Invoice ID"),
 *             @OA\Property(property="amount", type="number", format="decimal", minimum=0.01, example=5000.00, description="Amount to allocate"),
 *             @OA\Property(property="notes", type="string", nullable=true, example="Partial payment", description="Allocation notes")
 *         )
 *     )
 * )
 */
class AccountingSchemas
{
    // This class only contains OpenAPI schema annotations
}
