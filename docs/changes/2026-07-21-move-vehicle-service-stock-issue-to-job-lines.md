# Move Vehicle Service stock issue into Job Lines

## Trigger

The Vehicle Service job detail exposed a separate Inventory tab only to select a warehouse/location and issue pending inventory job lines. The normal user workflow already starts from Job Lines, so the extra tab added navigation without owning a separate business responsibility.

## Preserved ownership

```text
Vehicle Service Job Line → material requirement and customer pricing
Inventory                → authoritative stock availability and movement posting
Warehouse                → default warehouse/location configuration
```

The Job Lines UI orchestrates the existing Inventory issue API. It does not calculate, deduct, or rewrite stock balances directly.

## Changes

- Added Issue warehouse and Issue location selectors to the new inventory Job Line form.
- Reused the Warehouse module's existing default warehouse and default-location APIs.
- Added `Add & issue stock` for new inventory lines.
- Kept `Add line` as a planning path that creates a pending inventory requirement without posting stock.
- When line creation succeeds but stock issue fails, the line remains saved as `Pending issue`; it is not automatically deleted or hidden.
- Added line-level `Issue stock` recovery for pending inventory lines.
- Preserved issue recovery for inventory-tracked combo/package child lines, even though combo children remain non-editable.
- Added a Stock column with `Pending issue` and `Issued` states.
- Reused the existing exact warehouse-location readiness check before posting.
- Removed the Vehicle Service Inventory tab and its replaced frontend component.
- Kept all inventory endpoints, movement services, permissions, warehouse/location validation, and movement history unchanged.

## Version and failure behavior

`Add & issue stock` intentionally uses the existing two owned commands:

```text
Create job line at version N
→ job becomes N + 1
→ issue that line through Inventory at version N + 1
→ job becomes N + 2
```

If the second command fails, the first command is retained as a visible pending line and the job remains at version `N + 1`. This is the smallest safe change because it preserves recovery without introducing a cross-module transaction or compensating deletion.

## Verification

```bash
npx vitest run resources/js/modules/vehicle-service/vehicleServiceJobLineInventoryFlow.test.ts
npx vitest run resources/js/modules/vehicle-service/pages/VehicleServiceJobDetailPage.test.ts
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```

Manual smoke path:

```text
Job Lines
→ Add inventory item
→ verify default warehouse/location
→ Add line (Pending issue)
→ Issue stock from row
→ verify Issued state

Job Lines
→ Add inventory item
→ Add & issue stock
→ verify line and stock movement

Combo/package
→ verify inventory child has Issue stock recovery
```

No schema, backend Inventory rule, permission, invoice, payment, or accounting change is included.
