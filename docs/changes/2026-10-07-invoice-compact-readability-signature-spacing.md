# Improve compact invoice readability and signature spacing

## Request

Make compact invoice print text easier to read and move the signature area farther below the payment summary table.

## Changes

- Increased the A5 and continuous-layout base font from 10 px to 12 px and line height from 1.2 to 1.25.
- Increased muted text from 9 px to 10 px for these layouts.
- Increased the compact signature table's top spacing from 8 px to 20 px.

## Verification

- Reviewed the shared print/PDF Blade template and confirmed both compact layouts use the updated rules.
- `git diff --check` passed.
- Automated tests were not run.
