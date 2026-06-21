# ReferenceData module

ReferenceData owns stable shared catalogs used by multiple business modules:

- countries
- currencies
- languages
- IANA timezones

Codes and timezone identifiers are immutable after creation. Records are activated or deactivated instead of soft-deleted so historical foreign-key relationships remain readable. Updates and status changes use `row_version` compare-and-swap checks and emit audit events.

Authenticated business forms use active-only paginated lookup endpoints. Full catalog administration, including inactive records, is permission-protected.
