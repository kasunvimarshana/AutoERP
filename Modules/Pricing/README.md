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

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
