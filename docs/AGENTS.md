# AutoERP Agent Core Axioms & Engineering Rules

## 1. Prime Directive: Understand First, Verify Second, Change Third
- Never act on assumptions or make changes blindly; fully understand requirements, system context, module ownership, and data consequences before modifying code.
- If requirements are unclear or information is missing, ask for clarification instead of guessing (evidence over guessing).
- Fix only the requested issue using the smallest safe change. Do not introduce regressions, side effects, or modify unrelated code.
- Verify before completion: verify that the solution satisfies all requirements, preserves data integrity, and passes relevant test suites before marking a task complete.

## 2. Root Cause Design over Compatibility Workarounds
- Prioritize correctness, readability, and long-term maintainability over backward compatibility.
- Do not carry forward legacy design flaws or preserve historical mistakes through compatibility patches or workarounds unless backward compatibility is a strict, documented requirement.
- Fix underlying designs and build a solid, maintainable foundation now.

## 3. Simplicity, Pragmatism & No Overengineering
- Prefer the simplest clean design that correctly solves the problem.
- Avoid premature optimization, unnecessary abstractions, speculative features, or generic frameworks.
- No dead code: do not introduce unused code, unnecessary helper abstractions, or speculative placeholders for hypothetical future requirements.
- "Wise developers know when to follow the framework and when to break away. Be wise."

## 4. Module Ownership & Clear Boundaries
- Each module owns only the logic, data models, and business responsibilities assigned to it.
- Fix problems at their source: any bug, missing functionality, or incomplete implementation must be addressed directly within the responsible module.
- Never compensate for bugs or deficiencies in one module by adding workarounds, shadow tables, or misplaced logic in another module.
- If business rules or logic are found in the wrong module, relocate them to the owning module rather than expanding the improper module's scope.
- Support vertical (feature-based) and horizontal (cross-cutting) development while preserving loose coupling, high cohesion, and clean architecture boundaries.

## 5. Backend Authority & Single Source of Truth
- Always resolve issues in the layer where the root cause exists.
- The backend is the single authoritative source of truth: core business logic, validations, state transitions, calculations, permissions, and data integrity enforcement must be strictly implemented and enforced on the backend.
- The frontend focuses on presentation, user interaction, and user experience. Frontend validations provide immediate user feedback only and never substitute for backend enforcement.
- Avoid duplicated business logic, duplicated configurations, or conflicting rules.

## 6. Concurrency, Atomicity & Transaction Immutability
- Concurrency axiom: Multiple actors operate on shared resources simultaneously at overlapping times; never assume exclusive access or sequential execution order.
- Every write mutation must be atomic, conflict-aware, and version-checked (`expected_version` / `row_version`).
- Store every transaction with its original values and never modify historical financial or operational ledger records in place.
- All adjustments, corrections, or reversals must be recorded as separate, linked audit records to preserve complete audit trails and accurate historical reporting.
- **Dual-Timestamp Architecture & Safe Backdated CRUD**:
  - Maintain a strict architectural separation between physical recording timestamps (`created_at`, `posted_at`, `reversed_at`, `updated_at`, `deleted_at`) and civil operational business dates (`invoice_date`, `movement_date`, `received_date`, `layer_date`, `payment_date`, `journal_date`).
  - Physical ingestion timestamps (`created_at`) are authoritative server-owned audit facts and must never be overridden or modified by client inputs.
  - Operational business dates govern accounting periods, chronological valuation queues, tax mapping, point-in-time aging, and ledger sequencing.
  - **Backdated CREATE**: Permitted for operational documents and ledger entries provided the civil date falls within an open accounting period, is not in the future (where prohibited, e.g. payments/expenses), and preserves chronological downstream consistency (e.g. projecting running balances and verifying that backdated stock issues do not cause intermediate negative stock across subsequent dates).
  - **UPDATE**: Draft records may be modified with optimistic concurrency control (`row_version`). Posted, approved, or finalized financial and operational records are strictly immutable in place; all adjustments must be separate linked audit records. Core transaction models must enforce this via lifecycle `booted()` hooks throwing `LogicException` on illegal mutations.
  - **DELETE**: Only draft records may be deleted. Posted or finalized documents must never be deleted in place; use governed cancellations or reversals. Core transaction models must enforce this via lifecycle `booted()` hooks throwing `LogicException`.
  - Point-in-time queries, aging calculations, and revision factor lookups must always filter and sequence by civil operational dates, never by physical server timestamps (`created_at`, `updated_at`).

## 7. Database Architecture & Explicit Migrations
- Use portable Laravel migration APIs.
- One table per migration file.
- Split migrations that create or modify multiple unrelated tables.
- Merge later `Schema::table()` patch migrations into the original table-creation migration where safe.
- In migrations, always prefer **KISS + Explicit over DRY**:
  - Migrations represent immutable database schema history, not application code.
  - Write every column, foreign key, constraint, and index explicitly line-by-line.
  - Never use dynamic loops, runtime conditionals, or programmatic column/foreign-key naming.
- Database relationship hygiene: identify and fix unnecessary, redundant, circular, bidirectional, or messy relationships with clear architectural justification and data integrity preservation.

## 8. Code Modularity & File Size Management
- Organize code into cohesive, modular units.
- Split large files when they violate clarity, separation of concerns, reduce readability, or increase cognitive complexity.
- Every file split must have a clear architectural reason (e.g., feature boundary, sub-responsibility, reuse). Avoid arbitrary or excessive fragmentation.

## 9. Zero Magic Values
- Never use hardcoded magic values, literals, or strings in application logic.
- Use PHP Backed Enums / TypeScript enums for predefined option sets.
- Use clearly named constants for shared fixed values.
- Use configuration files or environment variables for environment-specific or configurable parameters.

## 10. UI/UX Clarity & Cognitive Efficiency
- Design for speed, task completion efficiency, and clarity over information overload.
- Primary workflows (forms, creation dialogs, key actions) must display only essential fields and immediate context.
- Move auxiliary information (history, audit trails, reports, logs, analytics, advanced options) to separate views, tabs, or expandable progressive disclosure sections.
- **Never expose raw database IDs or internal primary keys to users**:
  - Present relational data in human-readable formats (codes, names, labels, badge status).
  - All relationship inputs must use controlled selection components (`LookupSelect`, dropdowns, autocomplete, visual selectors).
  - APIs must return structured related objects, not isolated foreign keys.
  - Prevent blind data entry with clear field descriptions, validation previews, and contextual guidance.

## 11. Security & Predictable Error Handling
- Security first: Never introduce security vulnerabilities, expose secrets, bypass authorization/authentication, or weaken input validation.
- Explicit error handling: Handle errors explicitly, consistently, and predictably. Never silently swallow failures.

## 12. Shared Project Memory via `/docs/changes`
- The project uses `/docs/changes` as its shared append-only memory.
- **Before starting any task**: Read recent records in `/docs/changes` to understand context and avoid repeating or conflicting work.
- **During the task**: Modify only the files necessary for the requested feature or fix; avoid touching unrelated files.
- **After completing the task**: Create a new record in `/docs/changes` detailing what was changed, why, and the verification results. Never edit or delete existing change records.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)

