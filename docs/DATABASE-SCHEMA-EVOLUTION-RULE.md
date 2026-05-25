# Database Schema Evolution Rule

## Purpose

Database structure, relationships, normalization, constraints, indexes, migrations, and schema design may be modified only when necessary to improve:

- Scalability
- Flexibility
- Performance
- Data integrity
- Maintainability
- Long-term architectural stability

## Strict Rules

- Do not modify schema for convenience or minor code changes.
- Do not change migrations without valid architectural reason.
- Do not refactor database unless there is a proven need.
- Do not duplicate tables or relationships unnecessarily.

## When Schema Can Be Changed

Schema changes are allowed only if one or more of the following is true:

- Current design cannot scale vertically or horizontally.
- Data integrity is compromised or at risk.
- Performance issues are structurally caused by schema design.
- Relationships are incorrectly modeled.
- Normalization or denormalization is required for real performance needs.
- Business domain requirements cannot be supported otherwise.

## Core Principle

Schema is a contract, not a flexible implementation detail.

- Code must adapt to schema.
- Schema should not constantly change to fit code.

## Final Rule

Any schema modification must be justified by:

- Real architectural need.
- Measurable improvement in system quality.
- Long-term system stability.

## Required Evidence For Schema Changes

Every schema-related pull request must include:

- Architectural reason and affected domain boundaries.
- Current limitation or failure mode.
- Expected measurable improvement (query cost, latency, lock time, integrity risk, or operability).
- Migration safety strategy (forward compatibility, rollback approach, and data migration plan).
- Impact analysis on dependent modules, foreign keys, and integrations.

Without this evidence, schema change proposals should be rejected.
