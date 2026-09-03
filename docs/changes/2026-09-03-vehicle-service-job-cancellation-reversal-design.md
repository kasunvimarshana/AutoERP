# Vehicle Service job cancellation reversal design

Date: 2026-09-03

## Scope

Reviewed how Vehicle Service job cancellation should reverse issued stock and remove labour commissions from payable totals, while restricting completed-job cancellation to an elevated administrator permission. This record documents the recommended implementation; no application code or database data was changed.

## Current behavior and gaps

- `draft`, `inspected`, `in_progress`, and `completed` jobs can transition to `cancelled` through the same `vehicle_service.jobs.transition` permission.
- The cancellation endpoint calls the generic status service. It changes the job status, synchronizes the vehicle status, and records status history, but does not reverse inventory or the Vehicle Service inventory-issue finance journal.
- Issuing stock creates a posted outbound Inventory movement, posts the related Vehicle Service finance source, links the original movement to the job line, and marks the line issued.
- Inventory already provides immutable movement reversal, and Finance provides immutable source/journal reversal. Vehicle Service currently has no orchestration that calls both and keeps the job consistent.
- Employee commission amounts are stored historical calculations. The Employee Commission Report already treats a cancelled job as cancelled commission and excludes it from payable/current totals. Zeroing or deleting assignment commission amounts would destroy history and is not required.
- A completed job can potentially have an active invoice link, including a partially invoiced job that remains completed. Such a job must not be cancelled until its invoice/payment documents are reversed or voided through their owning modules.

## Recommended ownership

Add a `VehicleServiceJobCancellationService` in the Vehicle Service module and route the existing cancel controller action through it. Keep `VehicleServiceStatusService` responsible for status/vehicle timeline changes, Inventory responsible for immutable stock reversal, and Finance responsible for immutable journal reversal.

The cancellation service should run one outer database transaction and:

1. Read the current job snapshot and enforce the required permission for its current status.
2. Reject stale `expected_version`, invalid statuses, and jobs with active invoice or payment documents.
3. Use the existing status service to lock the vehicle/job timeline and transition the job to cancelled.
4. Lock every linked original stock-issue movement in deterministic line order.
5. Reverse each posted movement through `InventoryFacade::reverse()`, producing immutable inbound reversal movements while preserving each line's link to the original issue.
6. Reverse each non-zero Vehicle Service inventory-issue finance source using the original movement ID, reversal date, actor, and required cancellation reason.
7. Return the refreshed cancelled job only after every reversal succeeds. Any failure must roll back the status change, status history, inventory reversals, and finance reversals together.

Do not clear `inventory_movement_id`, delete movements, overwrite quantities/costs, or zero stored commission amounts. The original records and their reversal links are the audit trail.

## Permission model

- Keep `vehicle_service.jobs.transition` for ordinary cancellation before completion.
- Add `vehicle_service.jobs.cancel_completed` for the `completed -> cancelled` transition.
- Enforce the elevated permission in the backend based on the current job status; frontend button visibility is only a usability aid.
- Assign the new permission only to the intended administrator role. The protected tenant Super Admin automatically receives active catalogue permissions through the existing access resolver.
- Continue rejecting cancellation from `invoiced`, `partially_paid`, and `paid` statuses.

## Commission behavior

Cancellation should preserve each assignment's original commission type, value, and amount. The cancelled job state is the reversal signal:

- normal report views exclude cancelled jobs and therefore reduce the employee's payable commission;
- `include_cancelled` views retain the original amount as cancelled commission for audit;
- if a future commission payout/settlement ledger is added, it must use a separate immutable reversal entry rather than editing the original assignment.

## UI behavior

- Show **Cancel job** for `draft`, `inspected`, and `in_progress` users with the normal transition permission.
- Show it for `completed` only when the user has `vehicle_service.jobs.cancel_completed`.
- Hide it for `invoiced`, `partially_paid`, `paid`, and already cancelled jobs.
- Require a cancellation reason and show a confirmation summary: stock quantities to be returned, commission amount moving to cancelled, and any blocking invoice/payment documents.
- After success, refresh the whole job rather than applying a status-only local patch so inventory reversal state, totals, timeline, and permissions are current.

## Required regression coverage

- pre-completion cancellation without issued stock;
- pre-completion cancellation with one and multiple issued stock lines;
- inventory quantity, valuation, and finance journal restored by immutable reversals;
- failure of any reversal rolls back the complete cancellation;
- retry/stale-version and already-reversed movement handling;
- technician and combo-supervisor commissions leave payable totals but remain in cancelled audit totals;
- completed cancellation denied without the elevated permission and allowed with it;
- active invoice/payment blocks cancellation even for an administrator;
- existing inspect, start, complete, issue, invoice, and payment flows remain unchanged.
