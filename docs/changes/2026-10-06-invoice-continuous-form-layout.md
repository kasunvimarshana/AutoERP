# Add a full-width continuous invoice layout

## Request

Use the user's Epson LQ-310 continuous paper form, measured at 8.5 inches wide by 5.5 inches high, across the usable width for invoice printing.

## Changes

- Added an organization-unit invoice layout option for an 8.5 × 5.5 inch continuous form.
- Set the page to 215.9 × 139.7 mm and applied the Epson LQ-310 continuous-feed side and end margins: 13 mm on each side and 4.2 mm at each end.
- Added the same custom page dimensions to browser printing and generated invoice PDFs.
- Reused the compact invoice content structure and increased the continuous layout headings for readability.
- Kept the standard A4 and compact A5 layout options available.

## Verification

- PHP syntax checks passed for modified Invoice PHP files.
- `git diff --check` passed.
- Automated tests were not run.
