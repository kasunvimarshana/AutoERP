# ADR-002: BCMath for All Monetary Calculations

**Date:** 2026-02-19
**Status:** Accepted

## Context

PHP's native `float` type uses IEEE 754 double-precision arithmetic, which cannot represent most decimal fractions exactly. For example, `0.1 + 0.2 !== 0.3` in floating-point. Financial calculations that accumulate rounding errors can produce incorrect invoice totals, tax amounts, or inventory valuations.

## Decision

All monetary and quantity values are stored as `DECIMAL(20, 8)` in the database and cast to `string` in Eloquent models. Every arithmetic operation uses PHP's **BCMath** extension with **8 decimal places of precision**:

```php
// Correct
$total = bcadd($subtotal, $taxAmount, 8);

// Never
$total = $subtotal + $taxAmount;
```

The precision scale (8) is fixed across the codebase. If higher precision is ever needed it must be changed consistently in all service classes.

## Consequences

- **Pro**: Exact decimal arithmetic; no floating-point drift in financial reports.
- **Pro**: DECIMAL columns preserve precision in the database.
- **Con**: Slightly more verbose code (`bcadd`, `bcmul` instead of `+`, `*`).
- **Con**: BCMath strings must be cast explicitly before JSON serialisation — Eloquent `'cast' => 'string'` handles this.
