# Organization-unit foundation correction

Date: 2026-06-26

## Why

The previous organization-unit implementation had a valid basic hierarchy but did not provide an authoritative end-to-end OU boundary. Current scope could be inferred from client request data, permissions were permissive, the materialized path was indexed beyond safe MySQL limits, raw storage paths were writable, lifecycle deletion semantics were unsafe, user assignment responsibilities were split, and typed configuration supported only exact OU lookup without parent inheritance. Feature modules also interpreted missing OU scope inconsistently.

## What changed

- Established `parent_id` as the hierarchy source of truth and retained `path`/`depth` as server-derived projections.
- Replaced the oversized unique path index with a fixed-size SHA-256 `path_hash` key.
- Made OU codes mandatory, tenant-unique, canonical, and immutable.
- Enforced active type/depth compatibility for create, update, activation, and complete subtree moves.
- Removed OU soft deletion. Units now follow deactivate → retire and remain as read-only historical aggregates.
- Added registered lifecycle blockers for active user assignments and active Auth sessions.
- Added module-owned permission definitions and server-side enforcement for unit, type, and document actions.
- Replaced permissive policy behavior and unversioned routes with explicit `/api/v1` management APIs.
- Removed request body/route/name/path/header scope inference. Current OU now comes from the authenticated session/token, with a server-validated Auth-owned switch command.
- Made operational feature routes require current OU context while tenant-global/administrative routes opt into optional scope explicitly.
- Moved user membership lifecycle and concurrency rules to the User module; removed ignored OU-role input.
- Serialized default assignment changes and blocked assignment to inactive/retired units.
- Revoked active Auth sessions and access/refresh tokens that remained bound to a removed user-to-OU membership.
- Replaced raw logo/document paths with private tenant/OU object keys, backend-derived metadata, MIME/size validation, scanning, checksums, and durable cleanup.
- Added optimistic compare-and-swap updates for types/documents and retained row-version checks for units.
- Added case-insensitive portable name keys for types/documents and soft-delete-safe active document uniqueness.
- Extended typed configuration definitions with explicit parent-hierarchy inheritance.
- Implemented exact OU → nearest active parent chain → tenant → global → definition-default resolution.
- Kept infrastructure configuration out of generic OU overrides. Database, encryption, auth, cache, session, queue, and logging remain platform-managed; genuine per-OU mail/storage/integration/database requirements require dedicated encrypted profiles/capabilities.
- Added hierarchy management, documents/logo, permission-aware navigation, human-readable selectors, optimistic concurrency, and safe scope switching to the frontend.
- Added OrganizationUnit architecture/regression tests and updated affected feature fixtures to create schema-valid OU hierarchies.
- Added transactional audit events for hierarchy/type/document mutations and authenticated OU scope switches.

## Ownership boundaries

- OrganizationUnit: hierarchy, types, lifecycle, private OU assets, ownership/hierarchy reads.
- User: user-to-OU assignments and default membership concurrency.
- Auth: session/token OU binding and scope switching.
- Configuration: typed definitions, values, revisions, inheritance, and consumers.
- Feature modules: classification and enforcement of their own OU-required, optional-with-global-fallback, or tenant-global data.

## Migration/runtime note

The corrected create migrations are intended for a fresh development database. Existing deployed schemas require a reviewed data migration and backfill plan for path hashes, type/name keys, lifecycle state, private object keys, and current Auth scope. Do not apply the rewritten create migrations blindly to production.
