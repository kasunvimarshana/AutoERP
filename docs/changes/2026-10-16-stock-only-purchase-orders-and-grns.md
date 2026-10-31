# Restrict purchase orders and GRNs to stock items

## Request

Allow only stock-type, stockable items on purchase orders and goods receipt notes, and restrict the PO item selector accordingly.

## Changes

- Added a stock-only item lookup that filters to active items with `item_type = stock` and `is_stockable = true`.
- Configured purchase order and Fast Purchase item selectors to use the stock-only lookup.
- Added a Purchase validation rule used by purchase order creation/update and GRN creation to reject items outside the stock-only rule.
- Revalidate GRN line items when posting, so existing invalid drafts cannot create inventory movements.

## Impact

- New and edited purchase orders can contain stockable stock items only. Non-stock/service/labour items can no longer be selected in PO flows.
- GRNs accept only those same items. Existing draft GRNs with other item types cannot be posted under this rule.
- Stock-item receipt and inventory posting behavior remains unchanged.
