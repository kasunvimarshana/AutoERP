# Sequence Module

Sequence module provides document numbering sequence management using the existing module architecture conventions.

## Migration Source of Truth

Sequence aggregate boundary is defined by:

- sequences

## Capabilities

- Sequence lifecycle management (create, update, list, get, delete)
- Scoped uniqueness by tenant, organization unit, document type, and period value
- Number formatting controls (prefix, suffix, padding)
- Next number state management per sequence

## Architecture

- Domain: sequence entity and normalization/invariant helpers
- Application: contract-driven CRUD use-cases and repository port
- Infrastructure: Eloquent model/repository and service provider bindings
- Presentation: REST controller, validation requests, and resources

## API Surface

Prefix: api/sequence

- sequences
