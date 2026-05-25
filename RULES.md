DATABASE SCHEMA EVOLUTION RULE

Database structure, relationships, normalization, constraints, indexes, migrations, and schema design MAY be modified ONLY when necessary to improve:

- Scalability
- Flexibility
- Performance
- Data integrity
- Maintainability
- Long-term architectural stability

------------------------------------------------------------
STRICT RULES
------------------------------------------------------------

- DO NOT modify schema for convenience or minor code changes
- DO NOT change migrations without valid architectural reason
- DO NOT refactor database unless there is a proven need
- DO NOT duplicate tables or relationships unnecessarily

------------------------------------------------------------
WHEN SCHEMA CAN BE CHANGED
------------------------------------------------------------

Only allow schema changes if:

- Current design cannot scale vertically or horizontally
- Data integrity is compromised or at risk
- Performance issues are structurally caused by schema design
- Relationships are incorrectly modeled
- Normalization/denormalization is required for real performance needs
- Business domain requirements cannot be supported otherwise

------------------------------------------------------------
CORE PRINCIPLE

Schema is a CONTRACT, not a flexible implementation detail.

Code MUST adapt to schema.
Schema should NOT constantly change to fit code.

------------------------------------------------------------
FINAL RULE

Any schema modification must be justified by:
- Real architectural need
- Measurable improvement in system quality
- Long-term system stability

GLOBAL ARCHITECTURE RULES (MANDATORY FOR ALL MODULES)

From now on, ALL development MUST strictly follow:

------------------------------------------------------------
1. DESIGN BY CONTRACT (DbC)
------------------------------------------------------------

- Every class/method MUST define clear contracts:
  - Preconditions (input requirements)
  - Postconditions (output guarantees)
  - Invariants (state rules)
- No silent failures
- No undefined behavior
- Validate boundaries at service/repository level

------------------------------------------------------------
2. INTERFACE-DRIVEN DEVELOPMENT (IDD)
------------------------------------------------------------

- ALWAYS depend on interfaces, NOT concrete classes
- Controllers → Service Interfaces only
- Services → Repository Interfaces only
- Never use direct implementations in business/application logic
- Everything MUST be injectable and mockable

------------------------------------------------------------
3. CORE MODULE ENFORCEMENT
------------------------------------------------------------

- MUST follow app/Modules/Core architecture strictly
- Core defines shared contracts, patterns, and abstractions
- No bypassing Core rules

------------------------------------------------------------
4. CLEAN ARCHITECTURE RULE
------------------------------------------------------------

- Strict separation of layers:
  - Domain
  - Application
  - Infrastructure
  - Interface (API / CLI)
- No cross-layer violations

------------------------------------------------------------
5. CODE QUALITY RULES
------------------------------------------------------------

- One class per file
- One responsibility per class
- No god classes
- No mixed concerns
- No tight coupling
- No hidden dependencies
- Prefer simplicity over complexity

------------------------------------------------------------
6. ANTI-PATTERNS (STRICTLY FORBIDDEN)
------------------------------------------------------------

- Direct Eloquent usage outside infrastructure
- Concrete dependency usage in business logic
- God repositories / services
- Over-engineering / unnecessary abstractions
- Mixing multiple responsibilities in one class
- Skipping interfaces

------------------------------------------------------------
FINAL RULE

All modules MUST be:
- Contract-driven (DbC)
- Interface-driven (IDD)
- Clean architecture compliant
- Fully decoupled
- Maintainable and scalable

------------------------------------------------------------

GLOBAL RULE: API MODULE + CORE MODULE DEPENDENCY CONTROL

------------------------------------------------------------
RULE STATEMENT
------------------------------------------------------------

Whenever implementing:
- new feature
- bug fix
- error handling improvement
- performance improvement
- API change

in any module (especially API layer),

ALWAYS evaluate dependency impact on:

app/Modules/Core

------------------------------------------------------------
MANDATORY BEHAVIOR
------------------------------------------------------------

IF the change:
- affects shared logic
- affects contracts/interfaces
- affects DTO patterns
- affects repository/service abstractions
- affects cross-module behavior
- affects architecture consistency

THEN:

- MUST update or extend app/Modules/Core accordingly
- MUST ensure Core remains consistent and minimal
- MUST follow Core contracts and patterns strictly

------------------------------------------------------------
CORE SAFETY RULE
------------------------------------------------------------

- Core is NOT optional
- Core is NOT bypassable
- Core is the architecture foundation

Any change in API that touches shared behavior MUST reflect in Core.

------------------------------------------------------------
ANTI-PATTERN (STRICTLY FORBIDDEN)
------------------------------------------------------------

- Fixing API in isolation while ignoring Core impact
- Duplicating Core logic inside modules
- Bypassing Core contracts
- Creating inconsistent implementations across modules
- Quick fixes that break architecture consistency

------------------------------------------------------------
DECISION RULE

Before implementing any change:

1. Does it affect shared behavior?
   → YES: update Core first or in parallel

2. Does it stay module-only?
   → YES: no Core change required

------------------------------------------------------------
GOAL

Maintain a consistent, scalable, and contract-driven system where:
- Core defines the rules
- Modules implement the rules
- No module violates Core architecture

------------------------------------------------------------

MIGRATION CHANGE CONTROL RULE (STRICT)

------------------------------------------------------------
CORE PRINCIPLE
------------------------------------------------------------

All migrations are part of:
app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

AND ARE THE SOURCE OF TRUTH FOR DATABASE STRUCTURE.

------------------------------------------------------------
RULE FOR CHANGES
------------------------------------------------------------

1. ADDING NEW STRUCTURES
- Adding new tables → ALLOWED
- Adding new fields/columns → ALLOWED (if required for new features)

2. MODIFYING EXISTING STRUCTURES
- Modifying existing tables/columns → ONLY allowed if absolutely necessary
  (design issue, scalability issue, or critical bug)

3. REMOVING STRUCTURES
- Removing existing tables/columns → ONLY allowed when explicitly required
  and after full impact analysis

------------------------------------------------------------
STRICT RULE (IMPORTANT)

Existing migrations MUST NOT be modified or deleted unless:

- There is a critical design flaw
- There is a scalability limitation
- There is a data integrity issue
- System cannot function correctly without the change

------------------------------------------------------------
FORBIDDEN ACTIONS

- DO NOT remove existing tables for convenience
- DO NOT modify schema to match new code changes
- DO NOT delete columns without strong justification
- DO NOT restructure database casually during feature development

------------------------------------------------------------
FEATURE DEVELOPMENT RULE

- New features MUST adapt to existing schema
- Schema changes are LAST RESORT, not first option
- Prefer extending system instead of modifying existing structure

------------------------------------------------------------
SCALABILITY RULE

Migration changes are ONLY allowed when:
- Vertical scaling is blocked
- Horizontal scaling is impacted
- Data model becomes invalid at scale

------------------------------------------------------------
FINAL RULE

- Additions → allowed
- Changes → restricted
- Deletions → strictly controlled

Migrations must remain stable, predictable, and safe.

------------------------------------------------------------

GLOBAL ARCHITECTURE RULE: SINGLE SOURCE OF TRUTH (MIGRATIONS)

------------------------------------------------------------
CORE PRINCIPLE
------------------------------------------------------------

The ONLY source of truth for system structure is:

app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

------------------------------------------------------------
RULE STATEMENT
------------------------------------------------------------

All system design decisions MUST be derived from migrations.

Migrations define:
- Database schema
- Table structures
- Relationships
- Constraints
- Data integrity rules
- Core domain boundaries

------------------------------------------------------------
STRICT RULES
------------------------------------------------------------

- Migrations are the PRIMARY source of truth
- Code MUST adapt to migrations
- NEVER modify schema to match code
- NEVER ignore migration structure
- NEVER design domain models without referencing migrations first

------------------------------------------------------------
DEVELOPMENT FLOW (MANDATORY)

1. Read migrations first
2. Derive domain model from schema
3. Design architecture based on schema
4. Implement code based on derived design
5. Validate consistency with migrations

------------------------------------------------------------
FEATURE DEVELOPMENT RULE

- New features MUST adapt to existing migrations
- Schema changes are LAST RESORT only
- Prefer extending existing structure over modifying it
- Do NOT introduce schema changes for convenience

------------------------------------------------------------
ARCHITECTURE RULE

- Migrations define structure
- Core defines architecture rules
- Modules implement business logic

------------------------------------------------------------
ANTI-PATTERNS (STRICTLY FORBIDDEN)

- Designing models without checking migrations
- Changing schema to match code
- Ignoring existing relationships
- Duplicating structure outside migration source
- Guessing database structure from code

------------------------------------------------------------
FINAL RULE

If migrations and code disagree:
→ MIGRATIONS WIN ALWAYS

Migrations are the single source of truth for the entire system.