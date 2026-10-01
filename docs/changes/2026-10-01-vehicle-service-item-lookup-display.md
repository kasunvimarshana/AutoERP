# Vehicle Service item lookup display

## Context

The job-line item lookup showed internal item codes for every result and only displayed service prices for batch options.

## Changes

- Show item names alone unless another active item in the tenant and organization scope has the same name; show the code for duplicate names to distinguish those items.
- Show resolved service prices for both batch and non-batch options, and identify items with no applicable service price.
- Preserve one option per active, unexpired batch with available stock. Batch-specific service prices continue to take precedence over item-level service prices.

## Reason

Users need readable item names and service-price context before selecting a job-line item, while batch-tracked stock must remain selectable by its actual available batch.
