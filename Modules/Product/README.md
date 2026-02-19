# Product Module

## Overview

The Product Module is a comprehensive product management system built following the Clean Architecture pattern (Controller → Service → Repository). It supports multiple product types, inventory tracking, hierarchical categories, and flexible unit of measure configurations.

## Features

### Product Types
- **Goods**: Physical products with inventory tracking
- **Services**: Non-physical services without inventory
- **Digital**: Digital products (downloads, software licenses)
- **Bundle**: Product bundles/kits
- **Composite**: Composite products made from other products

### Key Capabilities
- ✅ Multi-tenancy support (branch/organization isolation)
- ✅ Hierarchical product categories
- ✅ Product variants (size, color, etc.)
- ✅ Flexible unit of measure system with conversions
- ✅ Configurable buy/sell units
- ✅ Inventory tracking with reorder levels
- ✅ Product attributes/specifications (JSON)
- ✅ Multiple product images
- ✅ Pricing with min/max constraints
- ✅ Tax and discount configuration
- ✅ Low stock alerts
- ✅ Profit margin calculations
- ✅ Barcode support

## Architecture

### Models

#### Product
Main product model with comprehensive fields for all product types.

**Key Fields:**
- `sku`: Stock Keeping Unit (unique per branch)
- `name`: Product name
- `type`: goods|services|digital|bundle|composite
- `status`: active|inactive|discontinued|out_of_stock
- `cost_price`, `selling_price`: Pricing
- `track_inventory`: Enable/disable inventory tracking
- `current_stock`, `reorder_level`: Stock management
- `attributes`: JSON field for custom specifications
- `images`: JSON array of image paths

#### ProductCategory
Hierarchical categories with parent-child relationships.

**Key Features:**
- Self-referential parent-child structure
- Full path generation (e.g., "Electronics > Computers > Laptops")
- Active/inactive status
- Sort ordering

#### ProductVariant
Product variations (e.g., different sizes, colors).

**Key Features:**
- Variant-specific SKU and barcode
- Override pricing (inherits from parent if not set)
- Separate stock tracking per variant
- Variant attributes (JSON)
- Default variant marking

#### UnitOfMeasure
Units for product measurements (kg, liter, piece, etc.).

**Key Features:**
- Unit types (weight, volume, length, quantity)
- Base unit marking
- Active/inactive status

#### UoMConversion
Conversion factors between units.

**Example:**
- 1 kg = 1000 g (conversion_factor: 1000)
- 1 liter = 1000 ml (conversion_factor: 1000)

### Repositories

#### ProductRepository
Data access for products with methods for:
- Finding by SKU, barcode
- Searching products
- Low stock / out of stock queries
- Stock updates (increment/decrement)
- Filtering by type, category, manufacturer, brand

#### ProductCategoryRepository
Category data access with tree operations:
- Category tree retrieval
- Root categories
- Child category queries

#### UnitOfMeasureRepository
UoM data access with filtering by type and base units.

### Services

#### ProductService
Business logic for product operations:
- Auto-generate unique SKU if not provided
- Validate SKU and barcode uniqueness
- Stock management (add/remove stock)
- Inventory statistics
- Profitability calculations
- Status changes

#### ProductCategoryService
Category business logic:
- Validate category code uniqueness
- Prevent circular parent references
- Category tree operations

### Controllers

#### ProductController
RESTful API endpoints with full Swagger documentation:
- `GET /api/v1/products` - List products
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{id}` - Get product
- `PUT /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product

#### ProductCategoryController
Category API endpoints:
- `GET /api/v1/product-categories` - List categories
- `GET /api/v1/product-categories/tree` - Get category tree
- `POST /api/v1/product-categories` - Create category
- `GET /api/v1/product-categories/{id}` - Get category
- `PUT /api/v1/product-categories/{id}` - Update category
- `DELETE /api/v1/product-categories/{id}` - Delete category

## Database Schema

### Tables Created
1. `product_categories` - Hierarchical product categories
2. `unit_of_measures` - Units of measurement
3. `uom_conversions` - Unit conversion factors
4. `products` - Main products table
5. `product_variants` - Product variations

### Key Indexes
- SKU, barcode, name, category_id
- Type, status, manufacturer, brand
- Stock levels, featured flag
- Created_at for sorting

### Foreign Keys
- `category_id` → `product_categories.id` (nullOnDelete)
- `buy_unit_id`, `sell_unit_id` → `unit_of_measures.id` (nullOnDelete)
- `branch_id` → `branches.id` (cascadeOnDelete)
- Variant `product_id` → `products.id` (cascadeOnDelete)

### Constraints
- Unique: `(branch_id, sku)` per branch
- Unique: `(branch_id, code)` for categories

## API Examples

### Create Product
```json
POST /api/v1/products
{
  "name": "Laptop Computer",
  "description": "High-performance laptop",
  "type": "goods",
  "status": "active",
  "cost_price": 800.00,
  "selling_price": 1200.00,
  "track_inventory": true,
  "current_stock": 50,
  "reorder_level": 10,
  "manufacturer": "Dell",
  "brand": "XPS",
  "attributes": {
    "processor": "Intel i7",
    "ram": "16GB",
    "storage": "512GB SSD"
  }
}
```

### Update Product Stock
```php
$productService->addStock($productId, 100); // Add 100 units
$productService->removeStock($productId, 50); // Remove 50 units
$productService->updateStock($productId, 200); // Set to 200 units
```

### Create Category
```json
POST /api/v1/product-categories
{
  "name": "Electronics",
  "code": "ELEC",
  "description": "Electronic products",
  "is_active": true
}
```

### Create Subcategory
```json
POST /api/v1/product-categories
{
  "parent_id": 1,
  "name": "Computers",
  "code": "COMP",
  "description": "Computing devices"
}
```

## Enums

### ProductType
```php
ProductType::GOODS
ProductType::SERVICES
ProductType::DIGITAL
ProductType::BUNDLE
ProductType::COMPOSITE
```

### ProductStatus
```php
ProductStatus::ACTIVE
ProductStatus::INACTIVE
ProductStatus::DISCONTINUED
ProductStatus::OUT_OF_STOCK
```

## Usage Examples

### Check Stock Status
```php
$product = Product::find(1);

if ($product->needsReorder()) {
    // Trigger reorder process
    $quantity = $product->reorder_quantity;
}

if ($product->isOutOfStock()) {
    // Handle out of stock
}

$stockStatus = $product->stock_status; // 'in_stock', 'low_stock', 'out_of_stock', 'not_tracked'
```

### Calculate Profitability
```php
$profitability = $productService->calculateProfitability($productId);
// Returns: ['cost_price', 'selling_price', 'profit', 'profit_margin']

$product = Product::find(1);
$profit = $product->profit; // Amount
$margin = $product->profit_margin; // Percentage
```

### Category Tree
```php
$tree = $categoryService->getCategoryTree();
// Returns hierarchical category structure

$category = ProductCategory::find(1);
$fullPath = $category->full_path; // "Electronics > Computers > Laptops"
```

## Testing

Run feature tests:
```bash
php artisan test --filter=ProductApiTest
```

### Test Coverage
- ✅ List products
- ✅ Create product
- ✅ Show product
- ✅ Update product
- ✅ Delete product
- ✅ Validation errors
- ✅ Unique constraints (SKU, barcode)

## Integration Points

### Inventory Module
Products integrate with Inventory for stock movements and adjustments.

### Pricing Module
Products can have multiple price lists and dynamic pricing rules.

### Sales/Invoice Modules
Products are line items in sales transactions and invoices.

## Multi-Tenancy

All models support branch/organization isolation:
- Branch-specific products and categories
- Unique constraints per branch (SKU, category codes)
- Cross-branch queries when needed

## Validation Rules

### Product
- `name`: required, max:255
- `sku`: unique per branch, max:100
- `barcode`: unique, max:100
- `type`: enum(goods, services, digital, bundle, composite)
- `status`: enum(active, inactive, discontinued, out_of_stock)
- `cost_price`, `selling_price`: numeric, min:0, max:9999999999.99
- `current_stock`: integer, min:0

### Product Category
- `name`: required, max:255
- `code`: required, unique per branch, max:100
- `parent_id`: exists in product_categories (prevents circular refs)

## Performance Considerations

### Indexes
All frequently queried fields are indexed:
- SKU, barcode, name
- Type, status, category
- Stock levels, featured flag
- Created_at for date sorting

### Eager Loading
Use with relationships to avoid N+1:
```php
Product::with(['category', 'buyUnit', 'sellUnit', 'variants'])->get();
```

### Caching
Consider caching:
- Category trees
- Featured products
- Low stock alerts

## Future Enhancements

- [ ] Product bundles/composites implementation
- [ ] Product reviews and ratings
- [ ] Product comparison features
- [ ] Bulk import/export
- [ ] Product templates
- [ ] Advanced search/filtering
- [ ] Product tags/labels
- [ ] Related products
- [ ] Product history/audit trail
- [ ] Barcode generation

## License

Part of AutoERP - Modular SaaS Application
