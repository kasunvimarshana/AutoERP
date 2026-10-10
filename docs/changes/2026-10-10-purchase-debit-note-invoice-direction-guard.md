# Purchase debit-note invoice-direction allocation guard — 2026-10-10

## Source and scope

Authoritative starting branch: `worktree-0.0.8` at `33c05b27ed0dc364bc64ee8eef57df0586695ab4`. The 2026-10-10 TACGL/Vehicle Rental audit identified financial-owner gaps. This narrow change protects a proven accounting invariant inside **Purchase**, without modifying or reusing any retired Vehicle Rental code.

## Defect and root cause

`PurchaseDebitNoteService::assertAllocationScope()` previously matched tenant, organization, supplier party and posted state, but omitted the invoice's accounting **direction**. An outbound invoice row carrying the same supplier party identifiers could receive a Purchase debit-note credit allocation, reducing an outbound balance in a supplier-payable workflow. This is a financial integrity validation defect even if normal invoice creation usually prevents such an inconsistent combination.

## Change

- Check `InvoiceDirection::Inbound` explicitly inside the existing Purchase allocation scope validation. `InvoiceDirection` is the shared Invoice enum and the only source of truth for invoice direction.
- Extend the existing Purchase engine debit-note partial/full-allocation test: temporarily simulate an inconsistent outbound invoice, assert allocation rejection and unchanged note/invoice totals, then restore its inbound direction and verify the ordinary full allocation path remains functional.
- No change to Rental, Purchase document identity, migrations, relationships, UI, amounts, tax calculation, accounting profiles or previously posted records.

## Verification and limits

- Reviewed the precise source paths: Purchase debit-note service, Invoice direction enum/model, existing Purchase engine test, Rental owner-payable Invoice handoff, invoice balance settlement service.
- Source inspection shows the direction guard is missing at the baseline and both Invoice and Purchase already model the direction enum.
- **No executable Laravel unit/integration test is claimed for this patch.** The connected GitHub file-edit environment lacks a full checkout, Composer/npm dependencies and MySQL/MariaDB services; checkout from the execution container fails DNS resolution. A file-level patch/diff review is available via the PR. The authored regression test must execute in a dependency-backed checkout before a production release is certified.
- This guard does **not** fix or conceal the independently identified cross-currency note allocation and subledger/GL posting design gaps. These require owning-module analysis, migrations and reconciliation tests before new Rental debit/credit and expense-adjustment functionality can be described as complete.
