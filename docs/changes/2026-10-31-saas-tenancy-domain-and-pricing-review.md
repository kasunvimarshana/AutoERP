# SaaS tenancy domain and pricing review

Date: 2026-10-31

## Request

Review how AutoERP creates customer businesses, maps subdomains/domains, separates organization units and manages plans/subscriptions, to inform a future pricing and billing design.

## Work completed

- Added `docs/architecture/autoerp-saas-tenancy-and-pricing-review-2026-10-31.md` with the implemented architecture, onboarding flow, domain verification, infrastructure responsibilities, subscription/price behavior, limits, missing SaaS charging capabilities and pricing decisions still needed.
- Confirmed that a subscribing business is a Tenant, its company root/branches are Organization Units, and domains map to tenants. The supported database strategy is a shared schema with tenant-owned data, not a database or installation per subscriber.
- Reviewed the main relationships and their ownership: tenant-domain mappings, organization hierarchy, plan revisions, immutable subscription revisions and the current subscription pointer. These represent separate responsibilities; no schema relationship changes were justified or made.
- Identified an inconsistent terminal-state reassignment path: policy permits Assign after expiry/cancellation, but the lifecycle supplies a superseded ID that the revision creation guard rejects.
- Identified missing persisted schema-version arguments in plan updates/readiness comparisons: a price-only revision can drop Vehicle Rental, and readiness can miss that module removal.
- Clarified that organization caps include the protected root and storage caps currently cover Tenant documents, not all product data/files.

## Verification

- Read recent change records and inspected relevant migrations, services, routes, middleware, administration UI and configuration examples.
- Focused PHPUnit run passed: 23 tests, 43 assertions. Filter covered TenantDomainRulesTest, TenantSubscriptionMutationPolicyTest, TenantSubscriptionCommandInputTest and TenantDatabaseStrategyTest.
- Direct read-only PHP probes reproduced the reassignment invariant contradiction and the schema-version normalization difference. Full HTTP/database reproductions of those findings were not performed.
- A broader Tenant module test run was stopped after prolonged lack of progress; no completed full-suite result is claimed.
- `git diff --check` passed.

This was a review only. Application code, database schema/data, DNS, certificates, subscriptions and production infrastructure were not changed. Existing change records were not edited.
