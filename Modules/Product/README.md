# Product Module REST API Documentation

## Overview

This document describes the complete REST API layer for the Product module, including products, product categories, and units.

## Base URL

All endpoints are prefixed with: `/api/v1`

## Authentication

All endpoints require JWT authentication via the `Authorization: Bearer {token}` header and tenant context.

## Endpoints

### Products

#### List Products
```http
GET /api/v1/products
```

**Query Parameters:**
- `type` (string, optional): Filter by product type (good, service, bundle, composite)
- `category_id` (uuid, optional): Filter by category
- `is_active` (boolean, optional): Filter by active status
- `search` (string, optional): Search in name, code, or description
- `per_page` (integer, optional): Results per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Product Name",
      "code": "PRD12345",
      "type": "good",
      "type_label": "Physical Good",
      "description": "Product description",
      "category_id": "uuid",
      "category": {...},
      "buying_unit_id": "uuid",
      "buying_unit": {...},
      "selling_unit_id": "uuid",
      "selling_unit": {...},
      "metadata": {},
      "is_active": true,
      "has_inventory": true,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67
  }
}
```

#### Create Product
```http
POST /api/v1/products
```

**Request Body:**
```json
{
  "name": "Product Name",
  "code": "PRD12345",
  "type": "good",
  "category_id": "uuid",
  "buying_unit_id": "uuid",
  "selling_unit_id": "uuid",
  "description": "Product description",
  "metadata": {},
  "is_active": true
}
```

**Notes:**
- `code` is auto-generated if not provided
- Valid types: `good`, `service`, `bundle`, `composite`
- `is_active` defaults to `true`

#### Get Product
```http
GET /api/v1/products/{id}
```

#### Update Product
```http
PUT /api/v1/products/{id}
PATCH /api/v1/products/{id}
```

#### Delete Product
```http
DELETE /api/v1/products/{id}
```

### Product Bundles

#### List Bundle Items
```http
GET /api/v1/products/{product_id}/bundles
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "bundle_id": "uuid",
      "product_id": "uuid",
      "product": {...},
      "quantity": "10.0000000000",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

#### Add Bundle Item
```http
POST /api/v1/products/{product_id}/bundles
```

**Request Body:**
```json
{
  "product_id": "uuid",
  "quantity": 10.5
}
```

**Notes:**
- Product must be of type `bundle`
- Validates against circular references
- Product cannot contain itself

#### Remove Bundle Item
```http
DELETE /api/v1/products/{product_id}/bundles/{item_id}
```

### Product Composites

#### List Composite Parts
```http
GET /api/v1/products/{product_id}/composites
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "composite_id": "uuid",
      "component_id": "uuid",
      "component": {...},
      "quantity": "5.0000000000",
      "sort_order": 0,
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

#### Add Composite Part
```http
POST /api/v1/products/{product_id}/composites
```

**Request Body:**
```json
{
  "component_id": "uuid",
  "quantity": 5.0,
  "sort_order": 0
}
```

**Notes:**
- Product must be of type `composite`
- Validates against circular references
- `sort_order` is auto-generated if not provided

#### Remove Composite Part
```http
DELETE /api/v1/products/{product_id}/composites/{item_id}
```

### Product Categories

#### List Categories
```http
GET /api/v1/product-categories
```

**Query Parameters:**
- `parent_id` (uuid|null, optional): Filter by parent category (use "null" for root categories)
- `is_active` (boolean, optional): Filter by active status
- `search` (string, optional): Search in name, code, or description
- `per_page` (integer, optional): Results per page (default: 15)

#### Create Category
```http
POST /api/v1/product-categories
```

**Request Body:**
```json
{
  "name": "Category Name",
  "code": "CAT12345",
  "parent_id": "uuid",
  "description": "Category description",
  "metadata": {},
  "is_active": true
}
```

**Notes:**
- `code` is auto-generated if not provided
- `parent_id` can be null for root categories

#### Get Category
```http
GET /api/v1/product-categories/{id}
```

#### Update Category
```http
PUT /api/v1/product-categories/{id}
PATCH /api/v1/product-categories/{id}
```

**Notes:**
- Validates against circular parent references
- A category cannot be its own parent

#### Delete Category
```http
DELETE /api/v1/product-categories/{id}
```

**Notes:**
- Cannot delete category with child categories
- Cannot delete category with products

#### Get Child Categories
```http
GET /api/v1/product-categories/{id}/children
```

#### Get Products in Category
```http
GET /api/v1/product-categories/{id}/products
```

**Query Parameters:**
- `is_active` (boolean, optional): Filter by active status
- `per_page` (integer, optional): Results per page (default: 15)

### Units

#### List Units
```http
GET /api/v1/units
```

**Query Parameters:**
- `type` (string, optional): Filter by unit type
- `search` (string, optional): Search in name or symbol
- `per_page` (integer, optional): Results per page (default: 15)

#### Create Unit
```http
POST /api/v1/units
```

**Request Body:**
```json
{
  "name": "Kilogram",
  "symbol": "kg",
  "type": "weight",
  "metadata": {}
}
```

#### Get Unit
```http
GET /api/v1/units/{id}
```

#### Update Unit
```http
PUT /api/v1/units/{id}
PATCH /api/v1/units/{id}
```

#### Delete Unit
```http
DELETE /api/v1/units/{id}
```

**Notes:**
- Cannot delete unit used by products
- Deletes all conversions associated with the unit

### Unit Conversions

#### List Unit Conversions
```http
GET /api/v1/units/{unit_id}/conversions
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "from_unit_id": "uuid",
      "from_unit": {...},
      "to_unit_id": "uuid",
      "to_unit": {...},
      "conversion_factor": "1000.0000000000",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

#### Add Unit Conversion
```http
POST /api/v1/units/{unit_id}/conversions
```

**Request Body:**
```json
{
  "to_unit_id": "uuid",
  "conversion_factor": 1000.0
}
```

**Notes:**
- Can only convert between units of the same type
- Conversion factor must be greater than zero
- Each unit pair can only have one conversion

#### Convert Quantity
```http
POST /api/v1/units/convert
```

**Request Body:**
```json
{
  "from_unit_id": "uuid",
  "to_unit_id": "uuid",
  "quantity": 100.5
}
```

**Response:**
```json
{
  "from_unit_id": "uuid",
  "to_unit_id": "uuid",
  "from_quantity": "100.5",
  "to_quantity": "100500.0000000000",
  "conversion_factor": "1000.0000000000"
}
```

**Notes:**
- Supports reverse conversions automatically
- Uses BCMath for precision calculations
- Returns 404 if no conversion path exists

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message"
    ]
  }
}
```

### Not Found (404)
```json
{
  "message": "Resource not found."
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "message": "This action is unauthorized."
}
```

### Bad Request (400)
```json
{
  "message": "Error message describing the issue."
}
```

## Business Rules

### Product Types

1. **Good**: Physical product with inventory
2. **Service**: Non-physical offering without inventory
3. **Bundle**: Collection of products sold together
4. **Composite**: Product made from component parts

### Validation Rules

1. **Product Codes**: Must be unique per tenant, auto-generated if not provided
2. **Category Codes**: Must be unique per tenant, auto-generated if not provided
3. **Unit Symbols**: Must be unique per tenant
4. **Bundles**: Cannot contain circular references or itself
5. **Composites**: Cannot contain circular references or itself
6. **Categories**: Cannot create circular parent-child relationships
7. **Unit Conversions**: Can only convert between units of the same type

### Tenant Isolation

All operations are tenant-scoped:
- Products can only reference categories, units within the same tenant
- Bundle/composite items must belong to the same tenant
- Category parent must be in the same tenant
- Unit conversions only work within the same tenant

## Permissions Required

- `products.view`: View products
- `products.create`: Create products
- `products.update`: Update products and manage bundles/composites
- `products.delete`: Delete products
- `product-categories.view`: View categories
- `product-categories.create`: Create categories
- `product-categories.update`: Update categories
- `product-categories.delete`: Delete categories
- `units.view`: View units
- `units.create`: Create units
- `units.update`: Update units and manage conversions
- `units.delete`: Delete units

## Configuration

Product codes can be configured in `config/product.php`:

```php
'code' => [
    'auto_generate' => env('PRODUCT_CODE_AUTO_GENERATE', true),
    'prefix' => env('PRODUCT_CODE_PREFIX', 'PRD'),
    'length' => env('PRODUCT_CODE_LENGTH', 8),
],
```

## Implementation Notes

- All numeric calculations use BCMath for precision
- All database operations use transactions
- All responses include ISO 8601 timestamps
- Soft deletes are enabled for products and categories
- Audit logging is automatic for products (via Auditable trait)
