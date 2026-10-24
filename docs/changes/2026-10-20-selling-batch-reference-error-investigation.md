# Explain Selling batch reference validation error

## Request

Explain the 422 response from posting a sale that says batch- or lot-tracked items require a batch reference.

## Findings

- Selling forwards `batch_id` only when a batch is selected on the sale line.
- The Batch/Lot selector is inside the collapsed `Optional dimensions` section, so the current UI allows posting without it.
- Inventory validation rejects that sale when the selected item's tracking type is Batch or Lot and no batch ID was supplied. The sale transaction is rolled back.
- Expanding the sale line's dimensions and selecting a valid active batch/lot satisfies the current rule; if there are no available choices, stock needs a valid batch/lot recorded first.

## Changes

- No application code changed. Recorded the error path and immediate resolution.
