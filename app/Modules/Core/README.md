# Core Module

Core is the system-wide architectural baseline.

## Non-Negotiable Rules

- Domain and Application layers must not depend on framework or ORM types.
- Core must contain no module-specific workflows or business logic.
- All shared behavior must be exposed through contracts and patterns.
- Infrastructure adapters must implement Core contracts and remain swappable.
- No hidden defaults; use explicit configuration and constructor injection.
- Keep Core minimal; remove abstractions that are not actively reused.

## Required Dependency Direction

- `Domain` -> independent.
- `Application` -> depends on `Domain` only.
- `Infrastructure` -> depends on `Application` and framework.

## Canonical Standard

See `ARCHITECTURE-STANDARD.md` for:

- full audit,
- final structure,
- dependency flow,
- pattern usage,
- naming conventions,
- strict module implementation guidelines.

Simplification rule:

- Prefer DTO and Entity primitives over speculative interface layers.
