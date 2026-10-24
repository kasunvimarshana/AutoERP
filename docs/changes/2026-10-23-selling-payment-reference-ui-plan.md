# Plan Selling payment UI from reference image

## Request

Plan a Selling invoice payment screen based on the uploaded Vehicle Service payment reference image before implementation.

## Findings

- The existing Selling-specific payment view uses configured method dropdown rows, while the reference shows separate Cash, Card, and Bank Transfer amount sections with an always-visible payment summary.
- Selling receipts use the Payment module's draft, submit, approve, and post lifecycle. The reference's immediate Finalize payment action does not match that lifecycle.
- The general Payment create API currently accepts the applied cash amount, but has no dedicated tendered-cash or change fields. It also does not accept a card brand on payment creation lines, although payment line metadata can hold a brand in the Vehicle Service flow.

## Proposed direction

- Present configured Cash, Card, and Bank Transfer methods as compact amount sections, showing reference and additional details only when relevant.
- Compute applied cash and change in the UI, and persist any tender/change or card-brand facts through the Payment module if they are part of the receipt record.
- Keep the Selling receipt lifecycle and invoice allocation rules unchanged; label the primary action to match the draft receipt that is actually created.
- Preserve the existing credit, invoice print, and WhatsApp paths.

## Changes

- No application code changed. This record captures the pre-implementation plan.
