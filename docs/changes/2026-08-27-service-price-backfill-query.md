# Service-price backfill query

Date: 2026-08-27

## Purpose

Provide a safe SQL import that creates service prices from current sales prices for items that do not already have a current service price.

## Output

- `database/imports/2026-08-27-service-prices-from-sales.sql`

## Design

- Creates new immutable price revisions instead of modifying sales prices or historical records.
- Copies organization, variant, currency, UOM, amount, and effective-period scope from each current sales price.
- Generates the authoritative service scope key and a new lineage UUID for every inserted service-price revision.
- Skips the whole item when any current service price already exists, as requested.
- Uses an explicit `item_prices` write lock and one atomic `INSERT ... SELECT` statement to prevent concurrent duplicate creation.
- Can be rerun safely because items populated by the first run then satisfy the existing-service exclusion.

## Local-data assessment

- Found 704 current and currently effective sales-price rows across 704 items.
- Found 2 items with existing current service prices.
- Identified 702 service-price candidates.
- Confirmed the SQL scope-key formula matches every existing current sales-price scope.
