# Show Selling in tenant plan module selector

## Change

- Added Selling to the Operations group in the tenant plan editor so platform administrators can include it in a plan revision.
- Added a direct editor assertion for the Selling checkbox; the existing catalogue-wide assertion also verifies every supported module is rendered.
- No database migration is needed because tenant plan feature storage and backend validation already support the `selling` module code.

## Reason

Selling was present in the supported module catalogue and backend plan schema, but omitted from the editor's manually maintained module groups. That omission hid its checkbox from the plan UI.

## Notes

No previous change record was modified.
