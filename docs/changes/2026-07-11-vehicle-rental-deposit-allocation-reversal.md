# Vehicle Rental deposit allocation reversal

Date: 2026-07-11

## Context

A rental security-deposit receipt can be partially applied or forfeited against an invoice through a Payment-owned allocation. The existing deposit reversal workflow required the entire linked payment to be voided or reversed and also expected the invoice allocation to have already been reversed. Payment exposed only full-payment reversal, so one incorrect deposit application could not be corrected without undoing the complete receipt.

## Changes

- Payment now owns a versioned `PaymentAllocationReversalService` command for reversing one active invoice allocation.
- The command locks the posted payment and allocation, restores the invoice settlement, marks only that allocation reversed, and recalculates the payment allocation/unapplied balances.
- Vehicle Rental deposit application and forfeiture reversal delegate to that Payment command in the same transaction.
- Receipt and refund movement reversal still require the linked payment to be voided or reversed.
- Receipt reversal is blocked while active applications, forfeitures, or refunds remain.
- Deposit reversal now requires the deposit requirement version, linked payment version, and an explicit audit reason end to end.
- Deposit link status values are represented by `RentalDepositLinkStatus` instead of raw business literals.
- Focused Payment behavior and Vehicle Rental ownership-contract tests were added.

## Ownership and scope

Payment owns invoice-allocation settlement reversal and payment balance synchronization. Vehicle Rental owns deposit requirement and movement history. No schema changes, compatibility aliases, direct invoice mutation, or unrelated module changes were introduced.

## Verification

- The Payment reversal uses the existing Invoice settlement contract and Payment balance synchronizer.
- The Vehicle Rental service no longer requires an invoice-application payment to be fully reversed before correcting that allocation.
- Request, controller, API, and UI carry exact concurrency versions and the required reason.
- New and modified PHP sources were reviewed for syntax and owner boundaries; TypeScript caller contracts were reviewed for matching argument order.
- Full PHP, TypeScript, lint, build, and Vitest suites must be rerun in the project runtime after merge.
