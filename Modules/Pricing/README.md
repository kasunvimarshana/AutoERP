# Pricing Module

## Overview

The Pricing Module provides a comprehensive, extensible pricing engine for AutoERP with support for multiple pricing strategies, discounts, and tax calculations. It uses BCMath for precision-safe decimal calculations.

## Features

### ✅ Implemented Features

- **Extensible Pricing Engine** with multiple strategies
- **Multi-currency support** with currency_code field
- **BCMath precision** for all financial calculations
- **Priority-based rule evaluation**
- **Time-based pricing** (start_date, end_date)
- **Customer-specific pricing**
- **Location-based pricing**
- **Quantity breaks** (tiered pricing)
- **Discount calculation** with max/min limits
- **Tax calculation** with jurisdiction support
- **Clean architecture** (Repository → Service → Controller)

## Pricing Strategies

### 1. Flat Price Strategy
Fixed product selling price.

```php
$result = $pricingService->calculatePrice(
    productId: 1,
    quantity: '10',
    context: ['strategy' => 'flat']
);
```

### 2. Percentage Price Strategy
Cost + percentage markup.

```php
$result = $pricingService->calculatePrice(
    productId: 1,
    quantity: '10',
    context: [
        'strategy' => 'percentage',
        'markup_percentage' => '25.50'
    ]
);
```

### 3. Tiered Price Strategy
Volume-based pricing with quantity breaks.

```php
$result = $pricingService->calculatePrice(
    productId: 1,
    quantity: '100',
    context: [
        'strategy' => 'tiered',
        'price_list_id' => 5
    ]
);
```

### 4. Location-Based Price Strategy
Pricing based on location/branch.

```php
$result = $pricingService->calculatePrice(
    productId: 1,
    quantity: '10',
    context: [
        'strategy' => 'location_based',
        'location_code' => 'NYC'
    ]
);
```

### 5. Customer Group Price Strategy
Pricing based on customer or customer group.

```php
$result = $pricingService->calculatePrice(
    productId: 1,
    quantity: '10',
    context: [
        'strategy' => 'customer_group',
        'customer_id' => 42,
        'customer_group' => 'wholesale'
    ]
);
```

### 6. Rules-Based Price Strategy
Conditional pricing based on dynamic rules.

```php
$result = $pricingService->calculatePrice(
    productId: 1,
    quantity: '10',
    context: [
        'strategy' => 'rules_based',
        'customer_group' => 'vip',
        'day_of_week' => 'monday'
    ]
);
```

## Models

### PriceList
Main pricing list model supporting:
- Default, customer-specific, location-based pricing
- Multi-currency
- Time-based validity
- Priority ordering

### PriceListItem
Individual product prices within a price list:
- Min/max quantity for tiered pricing
- Discount percentage
- Final price calculation

### PriceRule
Dynamic pricing rules with:
- Conditions (JSON)
- Actions (JSON)
- Priority-based evaluation

### DiscountRule
Discount rules supporting:
- Percentage discounts
- Fixed amount discounts
- Buy X Get Y
- Bundle discounts
- Usage limits
- Applicable products/categories

### TaxRate
Tax rates by:
- Jurisdiction
- Product category
- Compound tax support
- Effective/expiry dates

## API Endpoints

### Price Calculation

```http
POST /api/v1/pricing/calculate
Content-Type: application/json

{
    "product_id": 1,
    "quantity": 10,
    "strategy": "flat",
    "customer_id": 5,
    "discount_code": "SAVE10",
    "calculate_tax": true,
    "jurisdiction": "NY"
}
```

### Cart Calculation

```http
POST /api/v1/pricing/calculate-cart
Content-Type: application/json

{
    "items": [
        {"product_id": 1, "quantity": 10},
        {"product_id": 2, "quantity": 5}
    ],
    "customer_id": 5,
    "discount_code": "CART20",
    "calculate_tax": true,
    "jurisdiction": "CA"
}
```

### Price Lists

```http
GET    /api/v1/price-lists
POST   /api/v1/price-lists
GET    /api/v1/price-lists/{id}
PUT    /api/v1/price-lists/{id}
DELETE /api/v1/price-lists/{id}
```

### Discount Rules

```http
GET    /api/v1/discount-rules
POST   /api/v1/discount-rules
GET    /api/v1/discount-rules/{id}
PUT    /api/v1/discount-rules/{id}
DELETE /api/v1/discount-rules/{id}
```

### Tax Rates

```http
GET    /api/v1/tax-rates
POST   /api/v1/tax-rates
GET    /api/v1/tax-rates/{id}
PUT    /api/v1/tax-rates/{id}
DELETE /api/v1/tax-rates/{id}
POST   /api/v1/tax-rates/calculate
```

## Usage Examples

### Creating a Price List

```php
$priceList = $priceListService->create([
    'name' => 'Wholesale Pricing',
    'code' => 'WHOLESALE-2024',
    'currency_code' => 'USD',
    'customer_group' => 'wholesale',
    'priority' => 10,
    'start_date' => now(),
    'end_date' => now()->addYear(),
]);
```

### Adding Price List Items

```php
$priceListItemService->create([
    'price_list_id' => $priceList->id,
    'product_id' => 1,
    'price' => '99.99',
    'min_quantity' => '10',
    'max_quantity' => '99',
    'discount_percentage' => '5.00',
]);
```

### Creating a Discount Rule

```php
$discountRule = $discountRuleService->create([
    'name' => '10% Off Storewide',
    'code' => 'SAVE10',
    'type' => 'percentage',
    'value' => '10.00',
    'min_purchase_amount' => '50.00',
    'max_discount_amount' => '100.00',
    'usage_limit' => 100,
    'start_date' => now(),
    'end_date' => now()->addMonth(),
]);
```

### Creating a Tax Rate

```php
$taxRate = $taxRateService->create([
    'name' => 'New York Sales Tax',
    'code' => 'NY-SALES',
    'rate' => '8.875',
    'jurisdiction' => 'NY',
    'effective_date' => now(),
]);
```

### Calculate Tax

```php
$tax = $taxRateService->calculateTax(
    amount: '100.00',
    jurisdiction: 'NY',
    productCategory: 'electronics',
    inclusive: false
);
// Returns: ['tax_amount' => '8.88', 'tax_rate' => '8.875', ...]
```

## Database Schema

### price_lists
- id, branch_id, name, code, description
- status, currency_code, is_default, priority
- customer_id, location_code, customer_group
- start_date, end_date
- timestamps, soft_deletes

### price_list_items
- id, price_list_id, product_id
- price, min_quantity, max_quantity, discount_percentage
- timestamps, soft_deletes

### price_rules
- id, branch_id, name, description
- is_active, priority, conditions (JSON), actions (JSON)
- start_date, end_date
- timestamps, soft_deletes

### discount_rules
- id, branch_id, name, code, description
- type, value, max_discount_amount, min_purchase_amount
- is_active, priority, conditions (JSON)
- applicable_products (JSON), applicable_categories (JSON)
- usage_limit, usage_count
- start_date, end_date
- timestamps, soft_deletes

### tax_rates
- id, branch_id, name, code, description
- rate, jurisdiction, product_category
- is_compound, is_active, priority
- effective_date, expiry_date
- timestamps, soft_deletes

## Testing

Run module tests:

```bash
php artisan test --filter=PricingApiTest
```

## Dependencies

- Product Module (for product pricing)
- Customer Module (for customer-specific pricing)
- Organization Module (for branch/tenant isolation)

## Architecture

```
┌─────────────┐
│ Controller  │ ← HTTP Request/Response
└──────┬──────┘
       │
┌──────▼──────┐
│   Service   │ ← Business Logic & Orchestration
└──────┬──────┘
       │
┌──────▼──────┐
│ Repository  │ ← Data Access
└──────┬──────┘
       │
┌──────▼──────┐
│    Model    │ ← Eloquent ORM
└─────────────┘
```

## Enums

- `PriceType`: flat, percentage, tiered, rules_based, location_based, customer_group
- `DiscountType`: percentage, fixed_amount, buy_x_get_y, bundle
- `PriceListStatus`: active, inactive, scheduled, expired
- `RuleConditionType`: quantity, subtotal, customer_group, location, product_category, date_range, day_of_week, time_of_day
- `RuleOperator`: equals, not_equals, greater_than, less_than, in, not_in, between, contains

## BCMath Usage

All financial calculations use BCMath for precision:

```php
// Addition
$total = bcadd($subtotal, $tax, 2);

// Subtraction
$discounted = bcsub($price, $discount, 2);

// Multiplication
$subtotal = bcmul($unitPrice, $quantity, 2);

// Division
$markupAmount = bcmul($cost, bcdiv($markup, '100', 4), 2);
```

## Security

- All endpoints require authentication (`auth:sanctum`)
- Tenant isolation via `TenantAware` trait
- Input validation via FormRequest classes
- Audit logging via `AuditTrait`

## License

Part of AutoERP - Modular SaaS Application
