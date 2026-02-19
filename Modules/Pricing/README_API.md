# Pricing Module REST API

Complete REST API for product pricing with support for 6 pricing strategies.

## Endpoints

### List Product Prices
```
GET /api/products/{product}/prices
```
Returns paginated list of prices for a product.

**Authentication:** Required  
**Authorization:** `pricing.view`

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "product": {
        "id": "uuid",
        "name": "Product Name",
        "sku": "SKU123"
      },
      "strategy": {
        "value": "tiered",
        "label": "Tiered Pricing"
      },
      "price": "100.00",
      "config": {...},
      "valid_from": "2024-01-01T00:00:00Z",
      "valid_until": null,
      "is_active": true
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Create Product Price
```
POST /api/products/{product}/prices
```

**Authentication:** Required  
**Authorization:** `pricing.create`

**Request Body:**
```json
{
  "product_id": "uuid",
  "location_id": "uuid (optional)",
  "strategy": "flat|percentage|tiered|volume|time_based|rule_based",
  "price": "100.00",
  "config": {},
  "valid_from": "2024-01-01T00:00:00Z (optional)",
  "valid_until": "2024-12-31T23:59:59Z (optional)",
  "is_active": true
}
```

**Config Examples:**

**Flat:**
```json
{
  "config": {}
}
```

**Percentage:**
```json
{
  "config": {
    "percentage": "10"
  }
}
```

**Tiered:**
```json
{
  "config": {
    "tiers": [
      {"min_quantity": "1", "price": "100.00"},
      {"min_quantity": "10", "price": "90.00"},
      {"min_quantity": "50", "price": "80.00"}
    ]
  }
}
```

**Volume:**
```json
{
  "config": {
    "thresholds": [
      {"min_quantity": "100", "discount_percentage": "5"},
      {"min_quantity": "500", "discount_percentage": "10"},
      {"min_quantity": "1000", "price": "85.00"}
    ]
  }
}
```

**Time-Based:**
```json
{
  "config": {
    "rules": [
      {
        "day_of_week": [1, 2, 3, 4, 5],
        "hour_start": 9,
        "hour_end": 17,
        "price": "120.00"
      },
      {
        "date_start": "2024-12-01",
        "date_end": "2024-12-31",
        "adjustment_percentage": "15"
      }
    ]
  }
}
```

**Rule-Based:**
```json
{
  "config": {
    "rules": [
      {
        "condition": {
          "field": "quantity",
          "operator": ">",
          "value": "100"
        },
        "action": {
          "type": "percentage_decrease",
          "value": "10"
        },
        "stop_on_match": false
      },
      {
        "condition": {
          "operator": "and",
          "conditions": [
            {"field": "customer_type", "operator": "=", "value": "wholesale"},
            {"field": "quantity", "operator": ">=", "value": "50"}
          ]
        },
        "action": {
          "type": "set_price",
          "value": "75.00"
        },
        "stop_on_match": true
      }
    ]
  }
}
```

### Get Product Price
```
GET /api/products/{product}/prices/{price}
```

**Authentication:** Required  
**Authorization:** `pricing.view`

### Update Product Price
```
PUT /api/products/{product}/prices/{price}
```

**Authentication:** Required  
**Authorization:** `pricing.update`

**Request Body:** Same as create, all fields optional

### Delete Product Price
```
DELETE /api/products/{product}/prices/{price}
```

**Authentication:** Required  
**Authorization:** `pricing.delete`

**Response:** 204 No Content

### Calculate Price
```
POST /api/pricing/calculate
```

Calculate price for a product with quantity and location.

**Authentication:** Required  
**Authorization:** `pricing.calculate`

**Request Body:**
```json
{
  "product_id": "uuid",
  "quantity": "25",
  "location_id": "uuid (optional)",
  "date": "2024-01-15T14:30:00Z (optional, for time-based)",
  "context": {
    "customer_id": "uuid",
    "customer_type": "wholesale"
  }
}
```

**Response:**
```json
{
  "data": {
    "product_id": "uuid",
    "product": {
      "id": "uuid",
      "name": "Product Name",
      "sku": "SKU123"
    },
    "quantity": "25",
    "location_id": "uuid",
    "strategy": {
      "value": "tiered",
      "label": "Tiered Pricing"
    },
    "calculation": {
      "base_price": "100.00",
      "unit_price": "90.00",
      "total_price": "2250.00",
      "breakdown": {
        "base_price": "100.00",
        "quantity": "25",
        "applied_tier": {
          "min_quantity": "10",
          "price": "90.00"
        },
        "tier_price": "90.00",
        "final_unit_price": "90.00",
        "final_total_price": "2250.00"
      }
    },
    "date": "2024-01-15T14:30:00Z",
    "calculated_at": "2024-01-15T14:30:45Z"
  }
}
```

## Pricing Strategies

### 1. Flat
Simple flat price per unit, multiplied by quantity.

### 2. Percentage
Base price adjusted by percentage (positive for markup, negative for discount).

### 3. Tiered
Quantity-based pricing tiers. Different price per tier based on quantity breakpoints.

### 4. Volume
Total volume discounts. Applies discount or special price based on total quantity.

### 5. Time-Based
Dynamic pricing based on time periods:
- Day of week (0 = Sunday, 6 = Saturday)
- Hour ranges (24-hour format)
- Date ranges

### 6. Rule-Based
Metadata-driven rule engine with conditional logic:
- Field comparisons (quantity, location, customer_type, custom fields)
- Operators: =, !=, >, <, >=, <=, in, not_in, contains
- Actions: set_price, add, subtract, multiply, percentage_increase, percentage_decrease
- Compound conditions with AND/OR logic

## Features

- ✅ All 6 pricing strategies fully implemented
- ✅ Location-based pricing (null = default)
- ✅ Time-bound pricing (valid_from, valid_until)
- ✅ Detailed price breakdown in calculations
- ✅ BCMath precision for all calculations
- ✅ Tenant-scoped operations
- ✅ Comprehensive audit logging
- ✅ Transaction safety
- ✅ Policy-based authorization
- ✅ Full validation and error handling

## Authorization Permissions

- `pricing.view` - View prices
- `pricing.create` - Create prices
- `pricing.update` - Update prices
- `pricing.delete` - Delete prices
- `pricing.calculate` - Calculate prices

## Error Responses

All endpoints return standard JSON error responses:

```json
{
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

**Status Codes:**
- 200 - Success
- 201 - Created
- 204 - No Content (Delete)
- 400 - Bad Request
- 401 - Unauthorized
- 403 - Forbidden
- 404 - Not Found
- 422 - Validation Error
- 500 - Server Error
