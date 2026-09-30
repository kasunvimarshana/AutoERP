# Vehicle Service stock-issue reversal gap

## Request

Trace how a user reverses a stock item after it has been issued to a Vehicle Service job.

## Findings

- The UI exposes **Issue stock** from a Vehicle Service job's **Job lines** tab.
- The backend exposes pending issue lines and `POST /api/v1/vehicle-service/jobs/{job}/issue-inventory`.
- Issuing creates and posts an outbound inventory movement, posts the Vehicle Service parts-consumption finance journal, links the movement to the job line through `inventory_movement_id`, and sets the line status to `issued`.
- No Vehicle Service route, controller action, request, orchestration service method, frontend API call, or UI action exists for reversing/unissuing that stock issue.
- Issued lines are intentionally blocked from edit and delete operations while `inventory_movement_id` is present.
- Cancelling a Vehicle Service job only changes job/vehicle workflow state. It does not reverse issued inventory movements or their finance journals.
- Inventory has an internal movement-reversal service, but calling it alone would leave the Vehicle Service line and the Vehicle Service finance posting inconsistent. It is therefore not a valid user workaround.

## Correct implementation boundary

A complete reversal must be owned and orchestrated by the Vehicle Service module. In one atomic, conflict-aware transaction it must lock and validate the job, line, and original movement; create the immutable Inventory reversal through `InventoryFacade`; reverse the finance source posted for the original movement; move the current job-line state back to a pending/approved state without modifying historical movements; bump the job version; record the actor, reason, and audit event; and return the refreshed line/job state.

A line-scoped endpoint such as `POST /api/v1/vehicle-service/jobs/{job}/lines/{line}/reverse-inventory` and a guarded **Reverse issue** action on the Job lines tab would expose that workflow. Eligibility after job completion or invoicing requires an explicit business decision and must not be guessed.

No application code or database changes were made.
