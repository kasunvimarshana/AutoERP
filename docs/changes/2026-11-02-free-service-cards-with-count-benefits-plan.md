# Free Service Cards with count-based benefits plan

Date: 2026-11-02

## Request

Clarify that promotional Service Cards are issued free, support multiple templates and issued cards with date ranges, and can include count-based Body Wash or Wash & Vacuum rewards.

## Changes

- Refined the existing product plan so Service Cards support both immediate limited-use benefits and buy-N-get-M benefits, including mixed cards.
- Count-based card benefits reference the same Promotions-owned offer rule and progress ledger used by standalone Offers. Issuing a card enrolls its linked offer once, avoiding duplicated counting logic and double assignment.
- Specified that card issuance creates no invoice/payment, multiple card templates/cards are allowed, and every template has an explicit validity range.
- No application code, schema, data, or prior change record was modified.

## Verification

- Re-read the latest change records and reviewed the existing promotions architecture plan.
- Planning documentation only; no application tests apply.
