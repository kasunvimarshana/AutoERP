# Limit payment amount entry to two decimal places

## Request

Payment amount fields showed six fractional digits while entering amounts. Show and accept at most two fractional digits in the payment entry UI.

## Changes

- Limited the Selling payment Cash, Card, and Bank Transfer amount fields to two fractional digits.
- Applied the same entry limit to the standard Payment method amount field.
- Kept the existing six-digit internal decimal arithmetic and payment submission flow unchanged.

## Verification

- TypeScript typecheck and `git diff --check` passed.
- Automated tests were not run.
