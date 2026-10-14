# Improve compact A5 invoice readability

## Request

Increase compact A5 invoice text readability after the printer was configured for A5 and diagnose the unused right side shown in the printout.

## Changes

- Increased the compact A5 base font from 8 px to 10 px and line height from 1.15 to 1.2.
- Increased muted A5 text from 7 px to 9 px.
- Left the configured A5 page dimensions unchanged.

## Diagnosis

The supplied print photo shows tractor-feed perforations and paper wider than the invoice content. This suggests the physical continuous form is wider than A5, so an A5 invoice occupies only part of the sheet and leaves the remaining width blank. The target stock width and length cannot be reliably determined from the photo. Set the driver form to the actual stock dimensions if the invoice should use that full width; use A5 single sheets if the intended output is a true A5 page.

## Verification

- Reviewed the rendered-template CSS and confirmed the compact A5 typography rules changed.
- Automated tests were not run.
