# Keep compact invoice print trace on the same page

## Request

Keep the printed-by trace on the invoice page and reduce signature label size where needed.

## Changes

- Reduced compact signature label text to 10 px and reduced the signature section spacing to preserve vertical room.
- Reduced compact print-trace text to 7 px, its top margin to 2 px, and prevented the trace from wrapping.

## Verification

- Reviewed compact A5 and continuous CSS rules in the shared print/PDF Blade template.
- `git diff --check` passed.
- Automated tests were not run.
