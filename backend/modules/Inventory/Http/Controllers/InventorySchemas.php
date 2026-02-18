<?php

namespace Modules\Inventory\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model representing an inventory item",
 *     required={"id", "name", "product_type", "status"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Unique product identifier"),
 *     @OA\Property(property="sku", type="string", nullable=true, maxLength=100, example="PRD-001", description="Stock keeping unit"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Laptop Computer", description="Product name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="High-performance laptop with 16GB RAM", description="Product description"),
 *     @OA\Property(property="product_type", type="string", enum={"inventory", "service", "bundle", "composite"}, example="inventory", description="Type of product"),
 *     @OA\Property(property="category_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440001", description="Product category ID"),
 *     @OA\Property(property="base_uom_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440002", description="Base unit of measure ID"),
 *     @OA\Property(property="track_inventory", type="boolean", example=true, description="Whether inventory tracking is enabled"),
 *     @OA\Property(property="track_batches", type="boolean", example=false, description="Whether batch tracking is enabled"),
 *     @OA\Property(property="track_serials", type="boolean", example=false, description="Whether serial number tracking is enabled"),
 *     @OA\Property(property="has_expiry", type="boolean", example=false, description="Whether product has expiry date"),
 *     @OA\Property(property="reorder_level", type="number", format="decimal", nullable=true, example=10.00, description="Minimum stock level before reorder"),
 *     @OA\Property(property="reorder_quantity", type="number", format="decimal", nullable=true, example=50.00, description="Quantity to order when restocking"),
 *     @OA\Property(property="cost_method", type="string", enum={"fifo", "lifo", "average", "standard"}, example="fifo", description="Cost calculation method"),
 *     @OA\Property(property="standard_cost", type="number", format="decimal", nullable=true, example=899.99, description="Standard cost per unit"),
 *     @OA\Property(property="average_cost", type="number", format="decimal", nullable=true, example=920.50, description="Average cost per unit"),
 *     @OA\Property(property="selling_price", type="number", format="decimal", nullable=true, example=1299.99, description="Default selling price"),
 *     @OA\Property(property="status", type="string", enum={"draft", "active", "inactive", "discontinued"}, example="active", description="Product status"),
 *     @OA\Property(property="barcode", type="string", nullable=true, maxLength=100, example="1234567890123", description="Product barcode"),
 *     @OA\Property(property="manufacturer", type="string", nullable=true, maxLength=255, example="Dell", description="Manufacturer name"),
 *     @OA\Property(property="brand", type="string", nullable=true, maxLength=255, example="Dell Latitude", description="Brand name"),
 *     @OA\Property(
 *         property="dimensions",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="weight", type="number", format="decimal", example=2.5, description="Product weight"),
 *         @OA\Property(property="weight_uom", type="string", example="kg", description="Weight unit of measure"),
 *         @OA\Property(property="length", type="number", format="decimal", example=35.0, description="Product length"),
 *         @OA\Property(property="width", type="number", format="decimal", example=25.0, description="Product width"),
 *         @OA\Property(property="height", type="number", format="decimal", example=2.0, description="Product height"),
 *         @OA\Property(property="dimension_uom", type="string", example="cm", description="Dimension unit of measure")
 *     ),
 *     @OA\Property(property="image_url", type="string", format="uri", nullable=true, example="https://example.com/products/laptop.jpg", description="Product image URL"),
 *     @OA\Property(property="custom_attributes", type="object", nullable=true, description="Custom product attributes"),
 *     @OA\Property(property="variants", type="array", @OA\Items(ref="#/components/schemas/ProductVariant"), description="Product variants"),
 *     @OA\Property(property="attributes", type="array", @OA\Items(ref="#/components/schemas/ProductAttribute"), description="Product attributes"),
 *     @OA\Property(
 *         property="stock_info",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="total_quantity", type="number", format="decimal", example=150.00, description="Total quantity across all warehouses"),
 *         @OA\Property(property="available_quantity", type="number", format="decimal", example=120.00, description="Available quantity (not reserved/allocated)"),
 *         @OA\Property(property="reserved_quantity", type="number", format="decimal", example=20.00, description="Reserved quantity"),
 *         @OA\Property(property="allocated_quantity", type="number", format="decimal", example=10.00, description="Allocated quantity")
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
 *     schema="ProductVariant",
 *     type="object",
 *     title="Product Variant",
 *     description="Product variant representing a variation of a product",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440003", description="Unique variant identifier"),
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Parent product ID"),
 *     @OA\Property(property="name", type="string", example="16GB RAM / 512GB SSD", description="Variant name"),
 *     @OA\Property(property="sku", type="string", nullable=true, example="PRD-001-16-512", description="Variant SKU"),
 *     @OA\Property(property="barcode", type="string", nullable=true, example="1234567890124", description="Variant barcode"),
 *     @OA\Property(property="price", type="number", format="decimal", nullable=true, example=1499.99, description="Variant price"),
 *     @OA\Property(property="cost", type="number", format="decimal", nullable=true, example=999.99, description="Variant cost")
 * )
 *
 * @OA\Schema(
 *     schema="ProductAttribute",
 *     type="object",
 *     title="Product Attribute",
 *     description="Custom attribute for a product",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440004", description="Unique attribute identifier"),
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Product ID"),
 *     @OA\Property(property="attribute_name", type="string", example="Color", description="Attribute name"),
 *     @OA\Property(property="attribute_value", type="string", example="Silver", description="Attribute value")
 * )
 *
 * @OA\Schema(
 *     schema="Warehouse",
 *     type="object",
 *     title="Warehouse",
 *     description="Warehouse model representing a storage facility",
 *     required={"id", "code", "name"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Unique warehouse identifier"),
 *     @OA\Property(property="code", type="string", maxLength=50, example="WH-001", description="Warehouse code (must be unique)"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Main Warehouse", description="Warehouse name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Primary distribution center", description="Warehouse description"),
 *     @OA\Property(
 *         property="address",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="address", type="string", example="123 Main Street", description="Street address"),
 *         @OA\Property(property="city", type="string", example="New York", description="City"),
 *         @OA\Property(property="state", type="string", example="NY", description="State/Province"),
 *         @OA\Property(property="country", type="string", example="USA", description="Country"),
 *         @OA\Property(property="postal_code", type="string", example="10001", description="Postal/ZIP code")
 *     ),
 *     @OA\Property(property="phone", type="string", nullable=true, maxLength=20, example="+1234567890", description="Contact phone number"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="warehouse@example.com", description="Contact email"),
 *     @OA\Property(property="manager_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440006", description="Warehouse manager user ID"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether warehouse is active"),
 *     @OA\Property(property="locations", type="array", @OA\Items(ref="#/components/schemas/Location"), description="Warehouse locations"),
 *     @OA\Property(
 *         property="stock_summary",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="total_products", type="integer", example=150, description="Total number of products"),
 *         @OA\Property(property="total_quantity", type="number", format="decimal", example=5000.00, description="Total quantity of all products"),
 *         @OA\Property(property="total_value", type="number", format="decimal", example=250000.00, description="Total value of inventory"),
 *         @OA\Property(property="low_stock_products", type="integer", example=10, description="Number of products with low stock")
 *     ),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Location",
 *     type="object",
 *     title="Location",
 *     description="Storage location within a warehouse",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440007", description="Unique location identifier"),
 *     @OA\Property(property="warehouse_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID"),
 *     @OA\Property(property="code", type="string", example="A-01-01", description="Location code"),
 *     @OA\Property(property="name", type="string", example="Aisle A - Rack 01 - Shelf 01", description="Location name"),
 *     @OA\Property(property="type", type="string", enum={"bin", "shelf", "rack", "floor", "zone"}, example="shelf", description="Location type"),
 *     @OA\Property(property="capacity", type="number", format="decimal", nullable=true, example=100.00, description="Maximum capacity"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether location is active")
 * )
 *
 * @OA\Schema(
 *     schema="StockLevel",
 *     type="object",
 *     title="Stock Level",
 *     description="Current stock level for a product in a warehouse",
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Product ID"),
 *     @OA\Property(property="variant_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440003", description="Variant ID"),
 *     @OA\Property(property="warehouse_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID"),
 *     @OA\Property(property="location_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440007", description="Location ID"),
 *     @OA\Property(property="quantity_on_hand", type="number", format="decimal", example=100.00, description="Physical quantity on hand"),
 *     @OA\Property(property="quantity_available", type="number", format="decimal", example=80.00, description="Available quantity (on hand - reserved - allocated)"),
 *     @OA\Property(property="quantity_reserved", type="number", format="decimal", example=15.00, description="Reserved quantity"),
 *     @OA\Property(property="quantity_allocated", type="number", format="decimal", example=5.00, description="Allocated quantity"),
 *     @OA\Property(property="batch_number", type="string", nullable=true, example="BATCH-2024-001", description="Batch number"),
 *     @OA\Property(property="serial_number", type="string", nullable=true, example="SN-123456789", description="Serial number"),
 *     @OA\Property(property="expiry_date", type="string", format="date", nullable=true, example="2025-12-31", description="Expiry date")
 * )
 *
 * @OA\Schema(
 *     schema="StockTransaction",
 *     type="object",
 *     title="Stock Transaction",
 *     description="Stock movement transaction record",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440008", description="Unique transaction identifier"),
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Product ID"),
 *     @OA\Property(property="variant_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440003", description="Variant ID"),
 *     @OA\Property(property="warehouse_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID"),
 *     @OA\Property(property="location_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440007", description="Location ID"),
 *     @OA\Property(property="transaction_type", type="string", enum={"receipt", "issue", "adjustment_in", "adjustment_out", "transfer_in", "transfer_out", "return", "reservation", "allocation", "release", "damaged"}, example="receipt", description="Type of transaction"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=50.00, description="Transaction quantity"),
 *     @OA\Property(property="unit_cost", type="number", format="decimal", nullable=true, example=899.99, description="Unit cost at time of transaction"),
 *     @OA\Property(property="total_cost", type="number", format="decimal", nullable=true, example=44999.50, description="Total cost of transaction"),
 *     @OA\Property(property="batch_number", type="string", nullable=true, example="BATCH-2024-001", description="Batch number"),
 *     @OA\Property(property="serial_number", type="string", nullable=true, example="SN-123456789", description="Serial number"),
 *     @OA\Property(property="expiry_date", type="string", format="date", nullable=true, example="2025-12-31", description="Expiry date"),
 *     @OA\Property(property="transaction_date", type="string", format="date-time", example="2024-01-15T10:30:00Z", description="Date and time of transaction"),
 *     @OA\Property(property="reference_type", type="string", nullable=true, example="purchase_order", description="Reference document type"),
 *     @OA\Property(property="reference_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440009", description="Reference document ID"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Received from supplier ABC", description="Transaction notes"),
 *     @OA\Property(
 *         property="timestamps",
 *         type="object",
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StoreProductRequest",
 *     type="object",
 *     title="Create Product Request",
 *     required={"name", "product_type", "cost_method", "status"},
 *     @OA\Property(property="sku", type="string", nullable=true, maxLength=100, example="PRD-001", description="Stock keeping unit (must be unique)"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Laptop Computer", description="Product name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="High-performance laptop with 16GB RAM", description="Product description"),
 *     @OA\Property(property="product_type", type="string", enum={"inventory", "service", "bundle", "composite"}, example="inventory", description="Type of product"),
 *     @OA\Property(property="category_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440001", description="Product category ID"),
 *     @OA\Property(property="base_uom_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440002", description="Base unit of measure ID"),
 *     @OA\Property(property="track_inventory", type="boolean", example=true, description="Enable inventory tracking"),
 *     @OA\Property(property="track_batches", type="boolean", example=false, description="Enable batch tracking"),
 *     @OA\Property(property="track_serials", type="boolean", example=false, description="Enable serial number tracking"),
 *     @OA\Property(property="has_expiry", type="boolean", example=false, description="Product has expiry date"),
 *     @OA\Property(property="reorder_level", type="number", format="decimal", nullable=true, example=10.00, description="Minimum stock level before reorder"),
 *     @OA\Property(property="reorder_quantity", type="number", format="decimal", nullable=true, example=50.00, description="Quantity to order when restocking"),
 *     @OA\Property(property="cost_method", type="string", enum={"fifo", "lifo", "average", "standard"}, example="fifo", description="Cost calculation method"),
 *     @OA\Property(property="standard_cost", type="number", format="decimal", nullable=true, example=899.99, description="Standard cost per unit"),
 *     @OA\Property(property="selling_price", type="number", format="decimal", nullable=true, example=1299.99, description="Default selling price"),
 *     @OA\Property(property="status", type="string", enum={"draft", "active", "inactive", "discontinued"}, example="active", description="Product status"),
 *     @OA\Property(property="barcode", type="string", nullable=true, maxLength=100, example="1234567890123", description="Product barcode"),
 *     @OA\Property(property="manufacturer", type="string", nullable=true, maxLength=255, example="Dell", description="Manufacturer name"),
 *     @OA\Property(property="brand", type="string", nullable=true, maxLength=255, example="Dell Latitude", description="Brand name"),
 *     @OA\Property(property="weight", type="number", format="decimal", nullable=true, example=2.5, description="Product weight"),
 *     @OA\Property(property="weight_uom", type="string", nullable=true, maxLength=20, example="kg", description="Weight unit of measure"),
 *     @OA\Property(property="length", type="number", format="decimal", nullable=true, example=35.0, description="Product length"),
 *     @OA\Property(property="width", type="number", format="decimal", nullable=true, example=25.0, description="Product width"),
 *     @OA\Property(property="height", type="number", format="decimal", nullable=true, example=2.0, description="Product height"),
 *     @OA\Property(property="dimension_uom", type="string", nullable=true, maxLength=20, example="cm", description="Dimension unit of measure"),
 *     @OA\Property(property="image_url", type="string", format="uri", nullable=true, example="https://example.com/products/laptop.jpg", description="Product image URL"),
 *     @OA\Property(property="custom_attributes", type="object", nullable=true, description="Custom product attributes as key-value pairs"),
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             required={"name"},
 *             @OA\Property(property="name", type="string", example="16GB RAM / 512GB SSD"),
 *             @OA\Property(property="sku", type="string", nullable=true, example="PRD-001-16-512"),
 *             @OA\Property(property="barcode", type="string", nullable=true, example="1234567890124"),
 *             @OA\Property(property="price", type="number", format="decimal", nullable=true, example=1499.99),
 *             @OA\Property(property="cost", type="number", format="decimal", nullable=true, example=999.99)
 *         ),
 *         description="Product variants"
 *     ),
 *     @OA\Property(
 *         property="attributes",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             required={"attribute_name", "attribute_value"},
 *             @OA\Property(property="attribute_name", type="string", example="Color"),
 *             @OA\Property(property="attribute_value", type="string", example="Silver")
 *         ),
 *         description="Product attributes"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateProductRequest",
 *     type="object",
 *     title="Update Product Request",
 *     @OA\Property(property="sku", type="string", nullable=true, maxLength=100, example="PRD-001", description="Stock keeping unit"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Laptop Computer", description="Product name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="High-performance laptop with 16GB RAM", description="Product description"),
 *     @OA\Property(property="product_type", type="string", enum={"inventory", "service", "bundle", "composite"}, example="inventory", description="Type of product"),
 *     @OA\Property(property="category_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440001", description="Product category ID"),
 *     @OA\Property(property="base_uom_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440002", description="Base unit of measure ID"),
 *     @OA\Property(property="track_inventory", type="boolean", example=true, description="Enable inventory tracking"),
 *     @OA\Property(property="track_batches", type="boolean", example=false, description="Enable batch tracking"),
 *     @OA\Property(property="track_serials", type="boolean", example=false, description="Enable serial number tracking"),
 *     @OA\Property(property="has_expiry", type="boolean", example=false, description="Product has expiry date"),
 *     @OA\Property(property="reorder_level", type="number", format="decimal", nullable=true, example=10.00, description="Minimum stock level before reorder"),
 *     @OA\Property(property="reorder_quantity", type="number", format="decimal", nullable=true, example=50.00, description="Quantity to order when restocking"),
 *     @OA\Property(property="cost_method", type="string", enum={"fifo", "lifo", "average", "standard"}, example="fifo", description="Cost calculation method"),
 *     @OA\Property(property="standard_cost", type="number", format="decimal", nullable=true, example=899.99, description="Standard cost per unit"),
 *     @OA\Property(property="selling_price", type="number", format="decimal", nullable=true, example=1299.99, description="Default selling price"),
 *     @OA\Property(property="status", type="string", enum={"draft", "active", "inactive", "discontinued"}, example="active", description="Product status"),
 *     @OA\Property(property="barcode", type="string", nullable=true, maxLength=100, example="1234567890123", description="Product barcode"),
 *     @OA\Property(property="manufacturer", type="string", nullable=true, maxLength=255, example="Dell", description="Manufacturer name"),
 *     @OA\Property(property="brand", type="string", nullable=true, maxLength=255, example="Dell Latitude", description="Brand name"),
 *     @OA\Property(property="weight", type="number", format="decimal", nullable=true, example=2.5, description="Product weight"),
 *     @OA\Property(property="weight_uom", type="string", nullable=true, maxLength=20, example="kg", description="Weight unit of measure"),
 *     @OA\Property(property="length", type="number", format="decimal", nullable=true, example=35.0, description="Product length"),
 *     @OA\Property(property="width", type="number", format="decimal", nullable=true, example=25.0, description="Product width"),
 *     @OA\Property(property="height", type="number", format="decimal", nullable=true, example=2.0, description="Product height"),
 *     @OA\Property(property="dimension_uom", type="string", nullable=true, maxLength=20, example="cm", description="Dimension unit of measure"),
 *     @OA\Property(property="image_url", type="string", format="uri", nullable=true, example="https://example.com/products/laptop.jpg", description="Product image URL"),
 *     @OA\Property(property="custom_attributes", type="object", nullable=true, description="Custom product attributes"),
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440003"),
 *             @OA\Property(property="name", type="string", example="16GB RAM / 512GB SSD"),
 *             @OA\Property(property="sku", type="string", nullable=true, example="PRD-001-16-512"),
 *             @OA\Property(property="barcode", type="string", nullable=true, example="1234567890124"),
 *             @OA\Property(property="price", type="number", format="decimal", nullable=true, example=1499.99),
 *             @OA\Property(property="cost", type="number", format="decimal", nullable=true, example=999.99)
 *         )
 *     ),
 *     @OA\Property(
 *         property="attributes",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440004"),
 *             @OA\Property(property="attribute_name", type="string", example="Color"),
 *             @OA\Property(property="attribute_value", type="string", example="Silver")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StoreWarehouseRequest",
 *     type="object",
 *     title="Create Warehouse Request",
 *     required={"code", "name"},
 *     @OA\Property(property="code", type="string", maxLength=50, example="WH-001", description="Warehouse code (must be unique)"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Main Warehouse", description="Warehouse name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Primary distribution center", description="Warehouse description"),
 *     @OA\Property(property="address", type="string", nullable=true, maxLength=500, example="123 Main Street", description="Street address"),
 *     @OA\Property(property="city", type="string", nullable=true, maxLength=100, example="New York", description="City"),
 *     @OA\Property(property="state", type="string", nullable=true, maxLength=100, example="NY", description="State/Province"),
 *     @OA\Property(property="country", type="string", nullable=true, maxLength=100, example="USA", description="Country"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, maxLength=20, example="10001", description="Postal/ZIP code"),
 *     @OA\Property(property="phone", type="string", nullable=true, maxLength=20, example="+1234567890", description="Contact phone number"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="warehouse@example.com", description="Contact email"),
 *     @OA\Property(property="manager_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440006", description="Warehouse manager user ID"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether warehouse is active")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateWarehouseRequest",
 *     type="object",
 *     title="Update Warehouse Request",
 *     @OA\Property(property="code", type="string", maxLength=50, example="WH-001", description="Warehouse code"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Main Warehouse", description="Warehouse name"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Primary distribution center", description="Warehouse description"),
 *     @OA\Property(property="address", type="string", nullable=true, maxLength=500, example="123 Main Street", description="Street address"),
 *     @OA\Property(property="city", type="string", nullable=true, maxLength=100, example="New York", description="City"),
 *     @OA\Property(property="state", type="string", nullable=true, maxLength=100, example="NY", description="State/Province"),
 *     @OA\Property(property="country", type="string", nullable=true, maxLength=100, example="USA", description="Country"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, maxLength=20, example="10001", description="Postal/ZIP code"),
 *     @OA\Property(property="phone", type="string", nullable=true, maxLength=20, example="+1234567890", description="Contact phone number"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="warehouse@example.com", description="Contact email"),
 *     @OA\Property(property="manager_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440006", description="Warehouse manager user ID"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether warehouse is active")
 * )
 *
 * @OA\Schema(
 *     schema="StockTransactionRequest",
 *     type="object",
 *     title="Stock Transaction Request",
 *     required={"product_id", "warehouse_id", "transaction_type", "quantity"},
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Product ID"),
 *     @OA\Property(property="variant_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440003", description="Variant ID"),
 *     @OA\Property(property="warehouse_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID"),
 *     @OA\Property(property="location_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440007", description="Location ID"),
 *     @OA\Property(property="transaction_type", type="string", enum={"receipt", "issue", "adjustment_in", "adjustment_out", "transfer_in", "transfer_out", "return", "reservation", "allocation", "release", "damaged"}, example="receipt", description="Type of transaction"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=50.00, description="Transaction quantity (must be greater than 0)"),
 *     @OA\Property(property="unit_cost", type="number", format="decimal", nullable=true, example=899.99, description="Unit cost"),
 *     @OA\Property(property="total_cost", type="number", format="decimal", nullable=true, example=44999.50, description="Total cost"),
 *     @OA\Property(property="batch_number", type="string", nullable=true, maxLength=100, example="BATCH-2024-001", description="Batch number"),
 *     @OA\Property(property="serial_number", type="string", nullable=true, maxLength=100, example="SN-123456789", description="Serial number"),
 *     @OA\Property(property="expiry_date", type="string", format="date", nullable=true, example="2025-12-31", description="Expiry date"),
 *     @OA\Property(property="transaction_date", type="string", format="date", nullable=true, example="2024-01-15", description="Transaction date"),
 *     @OA\Property(property="reference_type", type="string", nullable=true, maxLength=100, example="purchase_order", description="Reference document type"),
 *     @OA\Property(property="reference_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440009", description="Reference document ID"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Received from supplier ABC", description="Transaction notes")
 * )
 *
 * @OA\Schema(
 *     schema="StockAdjustmentRequest",
 *     type="object",
 *     title="Stock Adjustment Request",
 *     required={"product_id", "warehouse_id", "quantity", "reason"},
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Product ID"),
 *     @OA\Property(property="variant_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440003", description="Variant ID"),
 *     @OA\Property(property="warehouse_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID"),
 *     @OA\Property(property="location_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440007", description="Location ID"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=10.00, description="Adjustment quantity (positive for increase, negative for decrease)"),
 *     @OA\Property(property="reason", type="string", enum={"physical_count", "damaged", "expired", "theft", "correction", "other"}, example="physical_count", description="Reason for adjustment"),
 *     @OA\Property(property="batch_number", type="string", nullable=true, maxLength=100, example="BATCH-2024-001", description="Batch number"),
 *     @OA\Property(property="serial_number", type="string", nullable=true, maxLength=100, example="SN-123456789", description="Serial number"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Physical count adjustment", description="Adjustment notes")
 * )
 *
 * @OA\Schema(
 *     schema="StockReserveRequest",
 *     type="object",
 *     title="Stock Reserve Request",
 *     required={"product_id", "warehouse_id", "quantity", "reference_type", "reference_id"},
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Product ID"),
 *     @OA\Property(property="warehouse_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID"),
 *     @OA\Property(property="location_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440007", description="Location ID"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=5.00, description="Quantity to reserve (must be greater than 0)"),
 *     @OA\Property(property="reference_type", type="string", example="sales_order", description="Reference document type"),
 *     @OA\Property(property="reference_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010", description="Reference document ID"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Reserved for SO-12345", description="Reservation notes")
 * )
 *
 * @OA\Schema(
 *     schema="StockReleaseRequest",
 *     type="object",
 *     title="Stock Release Request",
 *     required={"product_id", "warehouse_id", "quantity", "release_type", "reference_type", "reference_id"},
 *     @OA\Property(property="product_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Product ID"),
 *     @OA\Property(property="warehouse_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID"),
 *     @OA\Property(property="location_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440007", description="Location ID"),
 *     @OA\Property(property="quantity", type="number", format="decimal", example=5.00, description="Quantity to release (must be greater than 0)"),
 *     @OA\Property(property="release_type", type="string", enum={"reserved", "allocated"}, example="reserved", description="Type of stock to release"),
 *     @OA\Property(property="reference_type", type="string", example="sales_order", description="Reference document type"),
 *     @OA\Property(property="reference_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010", description="Reference document ID"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Released from cancelled order", description="Release notes")
 * )
 *
 * @OA\Schema(
 *     schema="BulkImportRequest",
 *     type="object",
 *     title="Bulk Import Products Request",
 *     required={"products"},
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"name", "product_type"},
 *             @OA\Property(property="sku", type="string", nullable=true, example="PRD-001"),
 *             @OA\Property(property="name", type="string", example="Laptop Computer"),
 *             @OA\Property(property="product_type", type="string", example="inventory")
 *         ),
 *         description="Array of products to import"
 *     )
 * )
 */
class InventorySchemas
{
    // This class exists only to hold OpenAPI schema definitions
}
