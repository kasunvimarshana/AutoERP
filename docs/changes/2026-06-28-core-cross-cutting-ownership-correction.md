# Core cross-cutting ownership correction

Date: 2026-06-28

## Scope

- Moved idempotency persistence and lifecycle from Core to the dedicated Idempotency module.
- Moved private filesystem infrastructure from Core to the dedicated PrivateObject module.
- Added canonical private object metadata with tenant and organization-unit integrity.
- Moved password hashing ownership from Core to Auth.
- Removed the generic Extension EAV, polymorphic attachment, and comment surface.
- Replaced Rental polymorphic attachments with explicit expense-document and custody-event-document relationships.

## Architectural decisions

- Core contains technical primitives only and does not own authentication, storage, or idempotency persistence.
- Private files are represented by canonical private-object metadata and explicit owner-module relationships.
- Arbitrary entity-type/entity-id relationships are not retained as a compatibility layer.
- Historical Rental evidence is retained through restrictive foreign keys and explicit owner relationships.
