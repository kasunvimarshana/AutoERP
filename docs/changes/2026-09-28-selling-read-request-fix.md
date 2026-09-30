# Use concrete requests for Selling read endpoints

## Change

- Added a concrete Selling-owned `SaleQueryRequest` for read endpoints.
- Updated the sales list and detail controller actions to use it instead of the abstract shared `TenantScopedRequest`.
- Added authenticated API regression coverage for the sales list and sale detail routes.
- No schema or API response changes were made; no migration is required.

## Reason

Laravel could not resolve the abstract `TenantScopedRequest` injected into the controller, causing a 500 response when loading the Selling workspace and sale details.

## Notes

No previous change record was modified.
