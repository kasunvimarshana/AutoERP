# Align promotion validity with the workspace date

Promotion availability, assignment, card issuance, redemption validation, invoice qualification, and activity labels now use the configured tenant or organization unit timezone for calendar-date rules. This fixes newly created offers and card designs being hidden during the hours when the Colombo date is ahead of UTC. Event timestamps remain timestamps.

The Laravel application timezone now reads `APP_TIMEZONE`, matching the existing environment example. The workspace timezone default follows that application timezone unless a tenant or organization unit overrides it. The local `.env` was set to `Asia/Colombo` for the current workspace; deployments need their own environment value.

Added an integration test at the UTC/Colombo date boundary that creates an offer and card design, verifies their job availability, assigns the offer, and issues the card. Related count and card tests, PHP formatting, and syntax checks passed.
