<?php

namespace Modules\Purchasing\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="PurchaseOrder",
 *     type="object",
 *     title="Purchase Order",
 *     description="Purchase order object schema",
 *     required={"supplier_id", "order_date", "delivery_date", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="Purchase order ID"),
 *     @OA\Property(property="order_number", type="string", example="PO-2026-0001", description="Unique purchase order number"),
 *     @OA\Property(property="supplier_id", type="integer", example=5, description="Supplier ID"),
 *     @OA\Property(property="order_date", type="string", format="date", example="2026-02-06", description="Order creation date"),
 *     @OA\Property(property="delivery_date", type="string", format="date", example="2026-02-20", description="Expected delivery date"),
 *     @OA\Property(property="status", type="string", enum={"draft", "submitted", "approved", "received", "cancelled"}, example="draft", description="Purchase order status"),
 *     @OA\Property(property="total_amount", type="number", format="decimal", example=1500.00, description="Total order amount"),
 *     @OA\Property(property="currency", type="string", example="USD", description="Currency code"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Additional notes"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 *
 * @OA\Schema(
 *     schema="PurchaseOrderRequest",
 *     type="object",
 *     title="Purchase Order Request",
 *     description="Request body for creating/updating purchase orders",
 *     required={"supplier_id", "order_date", "delivery_date", "items"},
 *     @OA\Property(property="supplier_id", type="integer", example=5, description="Supplier ID"),
 *     @OA\Property(property="order_date", type="string", format="date", example="2026-02-06", description="Order creation date"),
 *     @OA\Property(property="delivery_date", type="string", format="date", example="2026-02-20", description="Expected delivery date"),
 *     @OA\Property(property="currency", type="string", example="USD", description="Currency code"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Additional notes"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Purchase order line items",
 *         @OA\Items(ref="#/components/schemas/PurchaseOrderItem")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PurchaseOrderItem",
 *     type="object",
 *     title="Purchase Order Item",
 *     description="Purchase order line item",
 *     required={"product_id", "quantity", "unit_price"},
 *     @OA\Property(property="product_id", type="integer", example=10, description="Product ID"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=50.00, description="Order quantity"),
 *     @OA\Property(property="unit_price", type="number", format="decimal", example=25.00, description="Unit price"),
 *     @OA\Property(property="tax_rate", type="number", format="decimal", example=10.00, description="Tax rate percentage"),
 *     @OA\Property(property="discount", type="number", format="decimal", example=5.00, description="Discount amount"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Item notes")
 * )
 *
 * @OA\Schema(
 *     schema="Supplier",
 *     type="object",
 *     title="Supplier",
 *     description="Supplier/Vendor object schema",
 *     required={"name", "email", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="Supplier ID"),
 *     @OA\Property(property="supplier_code", type="string", example="SUP-001", description="Unique supplier code"),
 *     @OA\Property(property="name", type="string", example="ABC Supplies Inc", description="Supplier name"),
 *     @OA\Property(property="email", type="string", format="email", example="contact@abcsupplies.com", description="Contact email"),
 *     @OA\Property(property="phone", type="string", example="+1-555-0123", description="Contact phone"),
 *     @OA\Property(property="address", type="string", example="123 Supply St", description="Street address"),
 *     @OA\Property(property="city", type="string", example="New York", description="City"),
 *     @OA\Property(property="state", type="string", example="NY", description="State/Province"),
 *     @OA\Property(property="country", type="string", example="USA", description="Country"),
 *     @OA\Property(property="postal_code", type="string", example="10001", description="Postal code"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active", description="Supplier status"),
 *     @OA\Property(property="payment_terms", type="string", example="Net 30", description="Payment terms"),
 *     @OA\Property(property="credit_limit", type="number", format="decimal", example=50000.00, description="Credit limit"),
 *     @OA\Property(property="tax_id", type="string", example="12-3456789", description="Tax identification number"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 *
 * @OA\Schema(
 *     schema="SupplierRequest",
 *     type="object",
 *     title="Supplier Request",
 *     description="Request body for creating/updating suppliers",
 *     required={"name", "email"},
 *     @OA\Property(property="name", type="string", example="ABC Supplies Inc", description="Supplier name"),
 *     @OA\Property(property="email", type="string", format="email", example="contact@abcsupplies.com", description="Contact email"),
 *     @OA\Property(property="phone", type="string", example="+1-555-0123", description="Contact phone"),
 *     @OA\Property(property="address", type="string", example="123 Supply St", description="Street address"),
 *     @OA\Property(property="city", type="string", example="New York", description="City"),
 *     @OA\Property(property="state", type="string", example="NY", description="State/Province"),
 *     @OA\Property(property="country", type="string", example="USA", description="Country"),
 *     @OA\Property(property="postal_code", type="string", example="10001", description="Postal code"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active", description="Supplier status"),
 *     @OA\Property(property="payment_terms", type="string", example="Net 30", description="Payment terms"),
 *     @OA\Property(property="credit_limit", type="number", format="decimal", example=50000.00, description="Credit limit"),
 *     @OA\Property(property="tax_id", type="string", example="12-3456789", description="Tax identification number")
 * )
 *
 * @OA\Schema(
 *     schema="GoodsReceipt",
 *     type="object",
 *     title="Goods Receipt",
 *     description="Goods receipt object schema",
 *     required={"purchase_order_id", "receipt_date", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="Goods receipt ID"),
 *     @OA\Property(property="receipt_number", type="string", example="GR-2026-0001", description="Unique receipt number"),
 *     @OA\Property(property="purchase_order_id", type="integer", example=5, description="Related purchase order ID"),
 *     @OA\Property(property="receipt_date", type="string", format="date", example="2026-02-06", description="Receipt date"),
 *     @OA\Property(property="warehouse_id", type="integer", example=1, description="Receiving warehouse ID"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "cancelled"}, example="pending", description="Receipt status"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Receipt notes"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 *
 * @OA\Schema(
 *     schema="GoodsReceiptRequest",
 *     type="object",
 *     title="Goods Receipt Request",
 *     description="Request body for creating/updating goods receipts",
 *     required={"purchase_order_id", "receipt_date", "items"},
 *     @OA\Property(property="purchase_order_id", type="integer", example=5, description="Related purchase order ID"),
 *     @OA\Property(property="receipt_date", type="string", format="date", example="2026-02-06", description="Receipt date"),
 *     @OA\Property(property="warehouse_id", type="integer", example=1, description="Receiving warehouse ID"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Receipt notes"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Receipt line items",
 *         @OA\Items(ref="#/components/schemas/GoodsReceiptItem")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="GoodsReceiptItem",
 *     type="object",
 *     title="Goods Receipt Item",
 *     description="Goods receipt line item",
 *     required={"product_id", "quantity_received"},
 *     @OA\Property(property="product_id", type="integer", example=10, description="Product ID"),
 *     @OA\Property(property="quantity_received", type="number", format="decimal", example=48.00, description="Quantity received"),
 *     @OA\Property(property="quantity_accepted", type="number", format="decimal", example=45.00, description="Quantity accepted"),
 *     @OA\Property(property="quantity_rejected", type="number", format="decimal", example=3.00, description="Quantity rejected"),
 *     @OA\Property(property="reason", type="string", nullable=true, description="Reason for rejection if any")
 * )
 */
class PurchasingSchemas
{
    // This class exists only to hold OpenAPI schema annotations
}
