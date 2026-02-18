<?php

namespace Modules\Sales\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     title="Customer",
 *     description="Customer model representing a sales customer with CRM capabilities",
 *     required={"id", "customer_code", "customer_name", "email"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Unique customer identifier"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Tenant identifier"),
 *     @OA\Property(property="customer_code", type="string", maxLength=50, example="CUST-001", description="Unique customer code"),
 *     @OA\Property(property="customer_name", type="string", maxLength=255, example="Acme Corporation", description="Customer name"),
 *     @OA\Property(property="email", type="string", format="email", example="contact@acme.com", description="Primary email address"),
 *     @OA\Property(property="phone", type="string", nullable=true, maxLength=20, example="+1-555-0123", description="Primary phone number"),
 *     @OA\Property(property="mobile", type="string", nullable=true, maxLength=20, example="+1-555-0124", description="Mobile phone number"),
 *     @OA\Property(property="fax", type="string", nullable=true, maxLength=20, example="+1-555-0125", description="Fax number"),
 *     @OA\Property(property="website", type="string", format="uri", nullable=true, example="https://www.acme.com", description="Customer website"),
 *     @OA\Property(property="tax_id", type="string", nullable=true, maxLength=50, example="12-3456789", description="Tax identification number"),
 *     @OA\Property(property="customer_tier", type="string", enum={"standard", "silver", "gold", "platinum", "vip"}, example="gold", description="Customer tier/category"),
 *     @OA\Property(property="payment_terms", type="string", enum={"net_7", "net_15", "net_30", "net_45", "net_60", "net_90", "due_on_receipt", "cash_on_delivery"}, example="net_30", description="Payment terms"),
 *     @OA\Property(property="payment_term_days", type="integer", example=30, description="Payment term in days"),
 *     @OA\Property(property="credit_limit", type="number", format="decimal", example=50000.00, description="Credit limit for customer"),
 *     @OA\Property(property="outstanding_balance", type="number", format="decimal", example=12500.00, description="Current outstanding balance"),
 *     @OA\Property(property="available_credit", type="number", format="decimal", example=37500.00, description="Available credit (credit_limit - outstanding_balance)"),
 *     @OA\Property(property="preferred_currency", type="string", example="USD", description="Preferred currency code"),
 *     @OA\Property(
 *         property="billing_address",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="address_line1", type="string", example="123 Main Street", description="Address line 1"),
 *         @OA\Property(property="address_line2", type="string", nullable=true, example="Suite 100", description="Address line 2"),
 *         @OA\Property(property="city", type="string", example="New York", description="City"),
 *         @OA\Property(property="state", type="string", example="NY", description="State/Province"),
 *         @OA\Property(property="country", type="string", example="USA", description="Country"),
 *         @OA\Property(property="postal_code", type="string", example="10001", description="Postal/ZIP code"),
 *         @OA\Property(property="formatted", type="string", example="123 Main Street, Suite 100, New York, NY 10001, USA", description="Formatted address")
 *     ),
 *     @OA\Property(
 *         property="shipping_address",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="address_line1", type="string", example="456 Oak Avenue", description="Address line 1"),
 *         @OA\Property(property="address_line2", type="string", nullable=true, example="Building B", description="Address line 2"),
 *         @OA\Property(property="city", type="string", example="Brooklyn", description="City"),
 *         @OA\Property(property="state", type="string", example="NY", description="State/Province"),
 *         @OA\Property(property="country", type="string", example="USA", description="Country"),
 *         @OA\Property(property="postal_code", type="string", example="11201", description="Postal/ZIP code"),
 *         @OA\Property(property="formatted", type="string", example="456 Oak Avenue, Building B, Brooklyn, NY 11201, USA", description="Formatted address")
 *     ),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether customer is active"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="VIP customer - handle with priority", description="Internal notes"),
 *     @OA\Property(property="custom_fields", type="object", nullable=true, description="Custom fields as key-value pairs"),
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
 *     schema="SalesOrder",
 *     type="object",
 *     title="Sales Order",
 *     description="Sales order representing a customer purchase order",
 *     required={"id", "order_number", "customer_id", "status", "order_date"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010", description="Unique order identifier"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Tenant identifier"),
 *     @OA\Property(property="order_number", type="string", example="SO-2024-001", description="Unique order number"),
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Customer identifier"),
 *     @OA\Property(property="customer", ref="#/components/schemas/Customer", description="Customer details"),
 *     @OA\Property(property="status", type="string", enum={"draft", "pending", "confirmed", "processing", "shipped", "delivered", "cancelled", "completed"}, example="confirmed", description="Order status"),
 *     @OA\Property(property="order_date", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Order date"),
 *     @OA\Property(property="delivery_date", type="string", format="date-time", nullable=true, example="2024-01-22T10:30:00Z", description="Expected delivery date"),
 *     @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main Street, Suite 100, New York, NY 10001, USA", description="Billing address"),
 *     @OA\Property(property="shipping_address", type="string", nullable=true, example="456 Oak Avenue, Building B, Brooklyn, NY 11201, USA", description="Shipping address"),
 *     @OA\Property(property="subtotal", type="number", format="decimal", example=10000.00, description="Order subtotal before tax and discount"),
 *     @OA\Property(property="tax_amount", type="number", format="decimal", example=800.00, description="Total tax amount"),
 *     @OA\Property(property="discount_amount", type="number", format="decimal", example=500.00, description="Total discount amount"),
 *     @OA\Property(property="total_amount", type="number", format="decimal", example=10300.00, description="Order total amount"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Customer requested express shipping", description="Order notes"),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/SalesOrderItem"), description="Order line items"),
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
 *     schema="SalesOrderItem",
 *     type="object",
 *     title="Sales Order Item",
 *     description="Line item in a sales order",
 *     required={"id", "order_id", "product_id", "quantity", "unit_price"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440020", description="Unique item identifier"),
 *     @OA\Property(property="order_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010", description="Sales order identifier"),
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440030", description="Product identifier"),
 *     @OA\Property(property="product_name", type="string", example="Laptop Computer", description="Product name (snapshot at order time)"),
 *     @OA\Property(property="product_sku", type="string", nullable=true, example="PRD-001", description="Product SKU (snapshot at order time)"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=5.00, description="Quantity ordered"),
 *     @OA\Property(property="unit_price", type="number", format="decimal", example=1299.99, description="Unit price at order time"),
 *     @OA\Property(property="tax_rate", type="number", format="decimal", nullable=true, example=8.00, description="Tax rate percentage"),
 *     @OA\Property(property="tax_amount", type="number", format="decimal", example=519.996, description="Tax amount for this line"),
 *     @OA\Property(property="discount_amount", type="number", format="decimal", example=100.00, description="Discount amount for this line"),
 *     @OA\Property(property="line_total", type="number", format="decimal", example=6919.95, description="Line total (quantity * unit_price + tax - discount)"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Customer prefers silver color", description="Line item notes"),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Quotation",
 *     type="object",
 *     title="Quotation",
 *     description="Sales quotation/quote sent to customer",
 *     required={"id", "quote_number", "customer_id", "status", "quote_date"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040", description="Unique quotation identifier"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Tenant identifier"),
 *     @OA\Property(property="quote_number", type="string", example="QT-2024-001", description="Unique quotation number"),
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Customer identifier"),
 *     @OA\Property(property="customer", ref="#/components/schemas/Customer", description="Customer details"),
 *     @OA\Property(property="quote_date", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Quotation date"),
 *     @OA\Property(property="valid_until", type="string", format="date-time", nullable=true, example="2024-01-30T23:59:59Z", description="Quotation expiry date"),
 *     @OA\Property(property="status", type="string", enum={"draft", "sent", "accepted", "rejected", "expired", "converted"}, example="sent", description="Quotation status"),
 *     @OA\Property(property="currency", type="string", example="USD", description="Currency code"),
 *     @OA\Property(property="exchange_rate", type="number", format="decimal", example=1.000000, description="Exchange rate to base currency"),
 *     @OA\Property(property="subtotal", type="number", format="decimal", example=8000.00, description="Quotation subtotal before tax and discount"),
 *     @OA\Property(property="discount_amount", type="number", format="decimal", example=400.00, description="Total discount amount"),
 *     @OA\Property(property="tax_amount", type="number", format="decimal", example=640.00, description="Total tax amount"),
 *     @OA\Property(property="total_amount", type="number", format="decimal", example=8240.00, description="Quotation total amount"),
 *     @OA\Property(property="terms_and_conditions", type="string", nullable=true, example="Payment due within 30 days of invoice date", description="Terms and conditions"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Special pricing for bulk order", description="Quotation notes"),
 *     @OA\Property(property="custom_fields", type="object", nullable=true, description="Custom fields as key-value pairs"),
 *     @OA\Property(property="converted_to_order_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440010", description="Converted sales order ID"),
 *     @OA\Property(property="converted_at", type="string", format="date-time", nullable=true, example="2024-01-20T14:30:00Z", description="Conversion timestamp"),
 *     @OA\Property(property="created_by", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440050", description="User who created the quotation"),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/QuotationItem"), description="Quotation line items"),
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
 *     schema="QuotationItem",
 *     type="object",
 *     title="Quotation Item",
 *     description="Line item in a quotation",
 *     required={"id", "quotation_id", "product_id", "quantity", "unit_price"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060", description="Unique item identifier"),
 *     @OA\Property(property="quotation_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440040", description="Quotation identifier"),
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440030", description="Product identifier"),
 *     @OA\Property(property="product_name", type="string", example="Laptop Computer", description="Product name"),
 *     @OA\Property(property="product_sku", type="string", nullable=true, example="PRD-001", description="Product SKU"),
 *     @OA\Property(property="description", type="string", nullable=true, example="High-performance laptop with 16GB RAM", description="Item description"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=3.00, description="Quantity quoted"),
 *     @OA\Property(property="unit_price", type="number", format="decimal", example=1299.99, description="Unit price"),
 *     @OA\Property(property="tax_rate", type="number", format="decimal", nullable=true, example=8.00, description="Tax rate percentage"),
 *     @OA\Property(property="tax_amount", type="number", format="decimal", example=311.9976, description="Tax amount for this line"),
 *     @OA\Property(property="discount_percent", type="number", format="decimal", nullable=true, example=5.00, description="Discount percentage"),
 *     @OA\Property(property="discount_amount", type="number", format="decimal", example=194.9985, description="Discount amount for this line"),
 *     @OA\Property(property="line_total", type="number", format="decimal", example=4016.97, description="Line total"),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StoreSalesOrderRequest",
 *     type="object",
 *     title="Store Sales Order Request",
 *     description="Request body for creating a new sales order",
 *     required={"customer_id", "order_date", "items"},
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Customer identifier"),
 *     @OA\Property(property="order_date", type="string", format="date", example="2024-01-15", description="Order date"),
 *     @OA\Property(property="delivery_date", type="string", format="date", nullable=true, example="2024-01-22", description="Expected delivery date"),
 *     @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main Street, New York, NY 10001", description="Billing address"),
 *     @OA\Property(property="shipping_address", type="string", nullable=true, example="456 Oak Avenue, Brooklyn, NY 11201", description="Shipping address"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Handle with care", description="Order notes"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Order line items",
 *         minItems=1,
 *         @OA\Items(
 *             type="object",
 *             required={"product_id", "quantity", "unit_price"},
 *             @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440030", description="Product identifier"),
 *             @OA\Property(property="quantity", type="number", format="decimal", example=5.00, minimum=0.01, description="Quantity to order"),
 *             @OA\Property(property="unit_price", type="number", format="decimal", example=1299.99, minimum=0, description="Unit price"),
 *             @OA\Property(property="tax_rate", type="number", format="decimal", nullable=true, example=8.00, minimum=0, maximum=100, description="Tax rate percentage"),
 *             @OA\Property(property="discount_amount", type="number", format="decimal", nullable=true, example=100.00, minimum=0, description="Discount amount")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateSalesOrderRequest",
 *     type="object",
 *     title="Update Sales Order Request",
 *     description="Request body for updating an existing sales order",
 *     @OA\Property(property="customer_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Customer identifier"),
 *     @OA\Property(property="status", type="string", enum={"draft", "pending", "confirmed", "processing", "shipped", "delivered", "cancelled", "completed"}, example="confirmed", description="Order status"),
 *     @OA\Property(property="order_date", type="string", format="date", example="2024-01-15", description="Order date"),
 *     @OA\Property(property="delivery_date", type="string", format="date", nullable=true, example="2024-01-22", description="Expected delivery date"),
 *     @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main Street, New York, NY 10001", description="Billing address"),
 *     @OA\Property(property="shipping_address", type="string", nullable=true, example="456 Oak Avenue, Brooklyn, NY 11201", description="Shipping address"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Updated notes", description="Order notes"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Order line items (if provided, replaces all existing items)",
 *         minItems=1,
 *         @OA\Items(
 *             type="object",
 *             required={"product_id", "quantity", "unit_price"},
 *             @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440030", description="Product identifier"),
 *             @OA\Property(property="quantity", type="number", format="decimal", example=5.00, minimum=0.01, description="Quantity to order"),
 *             @OA\Property(property="unit_price", type="number", format="decimal", example=1299.99, minimum=0, description="Unit price"),
 *             @OA\Property(property="tax_rate", type="number", format="decimal", nullable=true, example=8.00, minimum=0, maximum=100, description="Tax rate percentage"),
 *             @OA\Property(property="discount_amount", type="number", format="decimal", nullable=true, example=100.00, minimum=0, description="Discount amount")
 *         )
 *     )
 * )
 */
class SalesSchemas
{
    // This class exists solely to hold OpenAPI schema definitions
    // No implementation needed
}
