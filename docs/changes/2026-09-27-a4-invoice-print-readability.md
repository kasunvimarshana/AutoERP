# A4 invoice print readability

## Reason

The standard A4 invoice used a 12px base font, which appeared too small to read comfortably on the user's printed A4 page.

## Change

- Increase the A4 invoice base font to 16px and adjust its line height for legibility.
- Increase the A4 title, secondary text, warnings, and print trace proportionally.
- Keep the A5 compact layout and invoice content unchanged.

## Verification

- Reviewed the template diff to confirm the new rules apply only to `.layout-a4`.
- Physical printer output remains to be checked by the user.
