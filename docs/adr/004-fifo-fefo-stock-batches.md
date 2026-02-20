# ADR-004: FIFO/FEFO Inventory Valuation via stock_batches

**Date:** 2026-02-19
**Status:** Accepted

## Context

The inventory module must support accurate cost valuation for goods that:
1. Expire (food, pharmaceuticals) — **FEFO** (First Expired, First Out) minimises waste.
2. Have fluctuating purchase prices — **FIFO** (First In, First Out) matches cost to revenue accurately.

A single `cost_per_unit` column on `stock_items` only stores an average cost and cannot reconstruct historical cost layers.

## Decision

Introduce an **append-only `stock_batches` table** acting as a cost ledger:

- Each **inbound** `stock_movements` record (receipt, adjustment-in, purchase receipt) creates one `stock_batches` row with `quantity_remaining = quantity_received`.
- Each **outbound** movement (shipment, transfer-out) depletes the oldest/nearest-expiry batches by reducing `quantity_remaining` until the outbound quantity is satisfied.
- **FIFO** order: `ORDER BY received_at ASC`.
- **FEFO** order: `ORDER BY expiry_date ASC NULLS LAST, received_at ASC` (batches with no expiry are consumed last).

The `valuation_method` column on `stock_movements` records which strategy was used per movement.

`InventoryService::getFifoCost()` computes the current weighted-average unit cost from open batches (where `quantity_remaining > 0`).

## Consequences

- **Pro**: Accurate, auditable FIFO/FEFO cost per SKU/warehouse.
- **Pro**: Enables expiry reporting (`getExpiringBatches`).
- **Con**: `stock_batches` grows over time; periodic archival of fully-depleted batches (`quantity_remaining = 0`) is recommended.
- **Con**: Depletion logic requires `SELECT … FOR UPDATE` to avoid concurrent race conditions.
