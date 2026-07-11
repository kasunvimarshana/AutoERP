# Vehicle Rental deposit allocation reversal

Date: 2026-07-11

## Context

A rental security-deposit receipt can be partially applied or forfeited against an invoice through a Payment-owned allocation. The existing deposit reversal workflow required the entire linked payment to be voided or reversed and also expected the invoice allocation to have already been reversed. Payment exposed only full-payment reversal, so one incorrect deposit application could not be corrected without undoing the complete receipt.

The payment-allocation table also enforced one payment/invoice row forever. Keeping a reversed allocation as history therefore blocked a corrected allocation for the same payment and invoice.

## Changes

- Payment now owns a versioned `PaymentAllocationReversalService` command for reversing one active invoice allocation.
- The command locks the posted payment and allocation, restores the invoice settlement, marks only that allocation reversed, and recalculates the payment allocation/unapplied balances.
- Payment allocations now use a nullable active-identity slot: pending/active rows occupy the slot; reversal or void clears it. This preserves every historical row while allowing one corrected current allocation for the same payment and invoice.
- The Payment allocation create migration defines the active-identity slot and portable active-slot unique key as part of the fresh schema baseline; no follow-up patch migration is used.
- Full-payment reversal also clears active slots for reversed and voided allocations.
- Vehicle Rental deposit application and forfeiture reversal delegate to the Payment command in the same transaction.
- Receipt and refund movement reversal still require the linked payment to be voided or reversed.
- Receipt reversal is blocked while active applications, forfeitures, or refunds remain.
- Deposit reversal now requires the deposit requirement version, linked payment version, and an explicit audit reason end to end.
- Deposit link status values are represented by `RentalDepositLinkStatus` instead of raw business literals.
- Focused Payment behavior and Vehicle Rental ownership-contract tests were added.

## Ownership and scope

Payment owns allocation identity, invoice-settlement reversal, historical allocation retention, and payment balance synchronization. Vehicle Rental owns deposit requirement and movement history. The only schema change is the Payment-owned active-identity slot and its unique constraint in the owning create migration. No compatibility aliases, direct Invoice mutation, patch migrations, or unrelated module changes were introduced.

## Verification

- The Payment reversal uses the existing Invoice settlement contract and Payment balance synchronizer.
- The behavior test reverses one allocation, restores invoice/payment balances, creates a corrected allocation for the same payment/invoice, and retains the reversed row.
- The Vehicle Rental service no longer requires an invoice-application payment to be fully reversed before correcting that allocation.
- Request, controller, API, and UI carry exact concurrency versions and the required reason.
- The migration baseline test remains compatible because the schema change is defined in the owning one-table create migration.
- The custody confirmation frontend test scopes its action to the semantic desktop table, avoiding ambiguity from the shared responsive table's mobile and desktop renderings.
- Full PHP, TypeScript, lint, build, and Vitest suites must be rerun in the project runtime before deployment.
