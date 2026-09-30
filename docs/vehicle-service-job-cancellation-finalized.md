# Vehicle Service Job Cancellation — Finalized Development

Date: 2026-09-12

## Business rule

Job cancellation is controlled by job status and two explicit permissions. Role names are not hardcoded; administrators assign the appropriate permission to Cashier or administrator-level roles through the existing role-permission UI.

| Current job status | Required permission | Intended access |
| --- | --- | --- |
| `draft` | `vehicle_service.jobs.cancel` | Cashier or other approved operational role |
| `inspected` | `vehicle_service.jobs.cancel` | Cashier or other approved operational role |
| `in_progress` | `vehicle_service.jobs.cancel` and `vehicle_service.jobs.cancel_after_start` | Administrator-level role only |
| `completed` | `vehicle_service.jobs.cancel` and `vehicle_service.jobs.cancel_after_start` | Administrator-level role only |
| `invoiced`, `partially_paid`, `paid`, `cancelled` | Not directly cancellable | Resolve billing documents through their owning modules first |

`vehicle_service.jobs.transition` controls inspection, start, and completion only. It no longer grants cancellation access.

## User experience

1. The `Cancel job` action is visible only when the signed-in user has the permissions required for the job's current status.
2. Opening the action loads the server-owned cancellation preview.
3. The preview shows blockers, issued stock that will return, inventory value, and commission impact.
4. A non-empty cancellation reason is mandatory.
5. Confirmation submits the preview's current `row_version`; a concurrent change invalidates the confirmation and reloads the preview.

## Started-job inventory reversal

Starting a job automatically issues its reserved inventory. If an authorized administrator later cancels an `in_progress` or `completed` job:

- every linked posted issue is locked and validated against the job and job line;
- Inventory creates an immutable inbound reversal movement for the original quantity, cost, warehouse, location, batch, and serial dimensions;
- the original issue is marked `reversed` and remains linked to the job line;
- the reversal movement description records `Job cancellation: <entered cancellation reason>; Reversal of <original movement number>`;
- the linked Inventory/COGS finance journal is reversed through Finance;
- zero-cost issues return quantity without creating a finance journal;
- any missing, unrelated, or already-reversed issue blocks cancellation instead of returning stock twice.

The reversal is an accounting/system stock reversal. The confirmation still tells the administrator to confirm that the physical items were returned and are suitable for restocking.

## Non-started jobs

Cancelling a `draft` or `inspected` job releases all active job stock reservations. No stock reversal movement is created because stock has not yet been issued.

## Integrity guarantees

- Authorization is enforced by the backend cancellation service using the current locked job status; frontend visibility is only UX assistance.
- Job/vehicle status, reservations, inventory movements, and finance reversals commit in one transaction or roll back together.
- Cancellation requires a reason and expected version.
- Original movements, journals, line amounts, completion information, and status history are preserved.
- Repeated or stale cancellation cannot return stock twice.
- Active linked invoices or payments block cancellation and are never silently voided by Vehicle Service.

## Permission activation

After deployment, synchronize the module-owned permission catalogue:

```sh
php artisan db:seed --class='Modules\User\Database\Seeders\TenantPermissionSeeder' --force --no-interaction
```

Then refresh signed-in sessions and assign:

- `vehicle_service.jobs.cancel` to the intended Cashier/operational roles;
- both cancellation permissions to the intended administrator roles.

The protected Super Admin role receives the complete active permission catalogue during synchronization.
