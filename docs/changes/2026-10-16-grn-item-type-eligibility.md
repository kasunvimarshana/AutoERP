# GRN item type eligibility

## Request

Confirm whether Purchase GRNs accept only stock items or other item types as well.

## Findings

- `GoodsReceiptNoteService::create` validates each item as a tenant/organization scoped item but does not restrict the item type or require `is_stockable` for GRN lines. This applies to standalone GRNs and PO-based GRNs.
- Therefore GRNs can record stockable items and non-stock items such as services/labour, provided the item and other required references are valid.
- Posting inventory effects are separate: `PurchaseInventoryIntegrationService` creates inventory receipts only when `is_stockable` is true and the type is not `non_stock`, `service`, or `labour`. Such non-stockable lines can be included on the GRN, but do not increase inventory balances.
- Item types include stock, non-stock, service, labour, asset, consumable, package, and combo. The integration exclusion list means stockable asset/consumable/package/combo types can affect inventory if configured stockable.

## Changes

- No application code or business data was changed. Added this code-path explanation to the append-only change history.
