# Global Architecture Rules

## Purpose

These rules are mandatory for all modules under app/Modules and define non-negotiable engineering constraints for long-term stability, scalability, and maintainability.

## 1. Design by Contract (DbC)

- Every class and method must enforce clear contracts.
- Preconditions: validate input at the boundary (controller, command, service, repository).
- Postconditions: guarantee expected output shape and result semantics.
- Invariants: preserve domain state rules and integrity constraints.
- No silent failures. Return explicit failures or throw explicit exceptions.

## 2. Interface-Driven Development (IDD)

- Depend on interfaces, not concrete classes.
- Controllers and commands may depend only on service interfaces.
- Application services may depend only on repository interfaces and service contracts.
- Keep all dependencies injectable and mockable.

## 3. Core Module Enforcement

- Core architecture under app/Modules/Core defines shared contracts and abstractions.
- Do not bypass Core patterns and conventions.
- Reuse Core result and error abstractions for consistency.

## 4. Clean Architecture Layering

- Keep strict separation across Domain, Application, Infrastructure, and Interface layers.
- Domain must not depend on Application, Infrastructure, or Presentation concerns.
- Application must not depend on Presentation concerns.
- Infrastructure implements contracts; it must not leak into business logic.

## 5. Code Quality Constraints

- One class per file.
- One responsibility per class.
- No god classes.
- No mixed concerns.
- No hidden dependencies.
- Prefer simple explicit design over complex implicit behavior.

## 6. Forbidden Anti-Patterns

- Direct Eloquent usage outside Infrastructure.
- Concrete dependency usage in business logic.
- God repositories and god services.
- Unnecessary abstractions and over-engineering.
- Cross-layer leakage.

## 7. Simplicity First (KISS)

- Prefer the smallest design that satisfies the current requirement.
- Do not introduce abstractions, interfaces, services, or layers without an immediate and concrete need.
- Reuse existing Core and shared module patterns before creating new structures.
- Keep implementations readable, direct, and easy to reason about locally.
- Avoid speculative future-proofing and indirection that does not solve a present problem.

### Decision Rule

- If an existing Core or shared pattern already solves the problem, reuse it.
- If a new layer does not create a necessary boundary or materially simplify maintenance, do not add it.
- When in doubt, choose the simpler implementation.

## Enforcement

- Architecture rules are enforced by automated tests in tests/Unit/Architecture.
- Any new violation must be treated as a regression and fixed before merge.
- Schema contract rules remain defined in docs/DATABASE-SCHEMA-EVOLUTION-RULE.md.
