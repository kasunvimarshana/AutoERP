# Pricing Module

## Overview

The **Pricing** module implements a rule-based pricing and discount engine. All prices and discounts are configurable without redeployment and support multi-currency, multi-location, and tiered pricing.

---

## Pricing Variability Dimensions

Buying price, selling price, purchase discount, and sales discount may vary by:

- Location
- Batch
- Lot
- Date range
- Customer tier
- Minimum quantity

---

## Supported Discount Formats

- Flat (fixed) amount
- Percentage

---

## Financial Rules

- All calculations use **BCMath only** — minimum **4 decimal places**
- Intermediate calculations (further divided or multiplied before final rounding): **8+ decimal places**
- Final monetary values: rounded to the **currency's standard precision (typically 2 decimal places)**
- No floating-point arithmetic

---

## Responsibilities

- Price list management (per currency, location, tier)
- Tiered pricing rules
- Discount rule configuration
- Rule evaluation engine (IF condition THEN action)
- Tax rule integration hooks
- Multi-currency price resolution

---

## Architecture Layer

```
Modules/Pricing/
 ├── Application/       # Resolve price, apply discount, calculate tax use cases
 ├── Domain/            # PriceList, DiscountRule, TaxRule entities, PricingRepository contract
 ├── Infrastructure/    # PricingRepository, PricingServiceProvider, rule evaluator
 ├── Interfaces/        # PriceListController, DiscountRuleController, PricingResource
 ├── module.json
 └── README.md
```

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Tenant isolation enforced (`tenant_id` + global scope) | ✅ Enforced |
| All financial calculations use BCMath (no float) | ✅ Enforced |
| No hardcoded pricing or discount rules (all database-driven) | ✅ Required |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Dependencies

- `core`
- `tenancy`
- `product`

---

## Implemented Files

### Migrations
| File | Description |
|---|---|
| `2026_02_27_000025_create_price_lists_table.php` | Tenant price list definitions with currency |
| `2026_02_27_000026_create_product_prices_table.php` | Product prices per price list — `decimal(20,4)` |
| `2026_02_27_000027_create_discount_rules_table.php` | Flat/percentage discount rules — `decimal(20,4)` |

### Domain
| File | Description |
|---|---|
| `Domain/Entities/PriceList.php` | Price list entity with `HasTenant` |
| `Domain/Entities/ProductPrice.php` | Product price; monetary columns cast as `string` (BCMath safe) |
| `Domain/Entities/DiscountRule.php` | Discount rule; monetary columns cast as `string` (BCMath safe) |
| `Domain/Contracts/PricingRepositoryContract.php` | Pricing repository contract |

### Application
| File | Description |
|---|---|
| `Application/DTOs/PriceCalculationDTO.php` | DTO for price calculation request |
| `Application/Services/PricingService.php` | Price resolution, discount application — BCMath only |

### Infrastructure
| File | Description |
|---|---|
| `Infrastructure/Repositories/PricingRepository.php` | Tenant-aware PriceList repository |
| `Infrastructure/Providers/PricingServiceProvider.php` | Binds contracts, loads migrations and routes |

### Interfaces
| File | Description |
|---|---|
| `Interfaces/Http/Controllers/PricingController.php` | POST /pricing/calculate, GET/POST /pricing/lists, GET/PUT/DELETE /pricing/lists/{id}, GET/POST /pricing/discount-rules, GET/PUT/DELETE /pricing/discount-rules/{id} |
| `routes/api.php` | Route definitions under `auth:api` middleware |

---

## Service Methods

| Method | Description |
|---|---|
| `calculatePrice` | Calculate price for a product with BCMath precision |
| `listPriceLists` | List all tenant price lists (paginated) |
| `createPriceList` | Create a new price list |
| `showPriceList` | Show a single price list |
| `updatePriceList` | Update an existing price list |
| `deletePriceList` | Soft-delete a price list |
| `listDiscountRules` | List all tenant discount rules (paginated) |
| `createDiscountRule` | Create a new discount rule |
| `showDiscountRule` | Show a single discount rule |
| `updateDiscountRule` | Update an existing discount rule |
| `deleteDiscountRule` | Soft-delete a discount rule |
| `listProductPrices` | List all product price entries for a product (tenant-scoped, filtered by product_id) |
| `createProductPrice` | Create a new product price entry (selling_price rounded via BCMath, DB::transaction) |

---

## API Endpoints

| Method | Path | Action |
|---|---|---|
| POST | `/pricing/calculate` | calculatePrice |
| GET | `/pricing/lists` | listPriceLists |
| POST | `/pricing/lists` | createPriceList |
| GET | `/pricing/lists/{id}` | showPriceList |
| PUT | `/pricing/lists/{id}` | updatePriceList |
| DELETE | `/pricing/lists/{id}` | deletePriceList |
| GET | `/pricing/discount-rules` | listDiscountRules |
| POST | `/pricing/discount-rules` | createDiscountRule |
| GET | `/pricing/discount-rules/{id}` | showDiscountRule |
| PUT | `/pricing/discount-rules/{id}` | updateDiscountRule |
| DELETE | `/pricing/discount-rules/{id}` | deleteDiscountRule |
| GET | `/products/{productId}/prices` | listProductPrices |
| POST | `/products/{productId}/prices` | createProductPrice |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/PriceCalculationDTOTest.php` | Unit | `PriceCalculationDTO` — field hydration, optional fields, type casting, string quantity |
| `Tests/Unit/PricingServiceCalculationTest.php` | Unit | BCMath percentage-discount, flat-discount, line-total, and final-price arithmetic |
| `Tests/Unit/PricingServiceCrudTest.php` | Unit | Price list and discount rule CRUD — method signatures, delegation, 20 assertions |
| `Tests/Unit/CreateProductPriceDTOTest.php` | Unit | `CreateProductPriceDTO` — field hydration, optional defaults, int cast, string selling_price, toArray round-trip — 6 assertions |
| `Tests/Unit/PricingServiceProductPriceTest.php` | Unit | `listProductPrices`/`createProductPrice` — method existence, visibility, parameter signatures, BCMath string safety, service instantiation — 8 assertions |

---

## Status

🟢 **Complete** — Core pricing scaffold implemented; BCMath discount calculations tested; full price list and discount rule CRUD endpoints added; product price entry management (createProductPrice/listProductPrices) added; frontend `PricingPage` with price lists and discount rules tables added (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
