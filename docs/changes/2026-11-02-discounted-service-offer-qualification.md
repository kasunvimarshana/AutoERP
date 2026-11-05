# Discounted service offer qualification

Date: 2026-11-02

## Request

Clarify whether a discounted service that still has a charge qualifies toward a buy-N-get-M offer.

## Changes

- Updated the Promotions product plan and database review: a posted service qualifies if its attributable amount remains above zero after line and allocated whole-job discounts. A fully free service does not qualify.
- Kept invoice posting as the counting point; later payment settlement is not required.
- Clarified that published offer definitions are immutable and earned reward counts derive from qualification and redemption events.
- No application code, migrations, database data, or previous change records were changed.

## Verification

- Reviewed the confirmed user answer against the current plan and invoice allocation design. Documentation only; no application tests apply.
