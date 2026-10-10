# Balance compact invoice line height and signature spacing

## Request

Reduce compact invoice line spacing while increasing the gap between the payment table and signature area, without moving the print trace to another page.

## Changes

- Restored compact A5 and continuous-layout line height to 1.2.
- Increased the signature table top gap from 10 px to 16 px.
- Kept the compact single-line print trace sizing in place.

## Verification

- Reviewed the shared compact print/PDF CSS rules.
- `git diff --check` passed.
- Automated tests were not run.
