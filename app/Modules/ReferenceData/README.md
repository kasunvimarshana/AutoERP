# ReferenceData module

ReferenceData owns stable shared catalogs used by multiple business modules:

- countries
- currencies
- languages
- IANA timezones

Codes and timezone identifiers are immutable after creation. Records are activated or deactivated instead of soft-deleted so historical foreign-key relationships remain readable. Updates and status changes use `row_version` compare-and-swap checks and emit audit events.

Authenticated business forms use active-only paginated lookup endpoints. Full catalog administration, including inactive records, is permission-protected.

## Catalogs & Timezone Standards

The `timezones` catalog stores canonical IANA timezone identifiers (e.g. `UTC`, `Asia/Colombo`, `America/New_York`, `Europe/London`). These records provide the authoritative, validated selection options for tenant and organization-unit configuration (`localization.timezone`), ensuring zero arbitrary strings can be injected into the temporal resolution pipeline.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
