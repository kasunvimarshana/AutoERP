Do not preserve legacy mistakes through compatibility patches. Build the correct foundation now to ensure long-term maintainability.

Avoid carrying forward historical mistakes for the sake of backward compatibility; fix the underlying design instead.

Do not preserve legacy design mistakes through compatibility patches. Prefer correcting the underlying design and establishing a maintainable foundation unless backward compatibility is a hard requirement.

Do not carry forward legacy design flaws for compatibility. Fix the root design and build a correct, maintainable foundation.

Prioritize correctness, readability, and maintainability over backward compatibility. Do not preserve legacy design flaws through compatibility patches; instead, fix the underlying design and build a clean, maintainable foundation unless backward compatibility is a strict requirement.

Prioritize correctness, readability, and long-term maintainability. Avoid compatibility patches that perpetuate legacy design flaws; fix the root design instead.

Prioritize correctness, readability, and long-term maintainability. Do not preserve legacy design flaws through compatibility patches, and do not overengineer solutions. Prefer the simplest design that correctly solves the problem.

Prioritize correctness, readability, and maintainability over backward compatibility. Avoid compatibility patches that perpetuate legacy design flaws, and do not overengineer. Build the simplest clean solution with a solid foundation.

Prioritize correctness, readability, and long-term maintainability. Do not preserve legacy design flaws through compatibility patches, and do not overengineer solutions. Never make assumptions or apply changes blindly; fully understand the requirements, context, and impact before making changes. Prefer the simplest solution that correctly addresses the root cause.

Prioritize correctness, readability, and maintainability. Fix root causes instead of preserving legacy flaws. Do not overengineer. Never act on assumptions or make changes blindly—ensure a complete understanding of the requirements, context, and consequences before proceeding.

Prioritize correctness, readability, and long-term maintainability. Fix root causes instead of preserving legacy design flaws through compatibility patches. Do not overengineer solutions. Never make assumptions or apply changes blindly—fully understand the requirements, context, and impact before proceeding. Avoid hardcoded magic values; use clearly named constants, configuration, or documented sources of truth. Prefer the simplest solution that correctly addresses the problem.

Prioritize correctness, readability, and maintainability above convenience. Do not preserve legacy design flaws through compatibility workarounds. Do not overengineer. Never make changes based on assumptions or incomplete understanding; verify requirements and assess impact first. Do not introduce hardcoded magic values—use named constants, configuration, or well-defined abstractions. Always prefer the simplest correct solution that resolves the root cause.

When modifying a module, any bug, missing functionality, or incomplete implementation in a related module must be addressed within the responsible module itself. Do not introduce workarounds, patches, or misplaced logic in other modules. Keep module responsibilities clear, and ensure all changes remain clean, readable, and maintainable.

Fix issues in the module where they belong. Do not compensate for bugs, missing functionality, or incomplete implementations in one module by adding workarounds to another. Maintain clear module boundaries and keep solutions clean, readable, and maintainable.

Fix problems at their source. Do not work around bugs, missing functionality, or incomplete implementations in other modules. Apply changes within the module that owns the responsibility, preserving clear boundaries, readability, and long-term maintainability.

Each module should contain only the logic and responsibilities it owns. If functionality belongs to another module, move it to the appropriate module instead of expanding the current module's scope. Maintain clear module boundaries and keep the codebase clean, readable, and maintainable.

Do not place logic, business rules, or responsibilities in modules that do not own them. When such code is encountered, relocate it to the appropriate module and preserve clear ownership boundaries, readability, and maintainability.

Support both vertical (feature-based) and horizontal (cross-cutting) development where appropriate. Design changes should enable feature enhancement without breaking existing functionality. Maintain data integrity at all times. Ensure loose coupling and high cohesion between modules. Follow clean architecture principles with clear separation of concerns, and respect module ownership boundaries. Prefer scalable and maintainable designs over tightly coupled or rigid implementations.

Support vertical feature development and necessary horizontal cross-cutting improvements without violating module boundaries. Ensure all changes preserve data integrity, maintain loose coupling, and follow clean architecture principles. Enhance features in a way that does not introduce unnecessary dependencies or architectural complexity. Prioritize scalability, maintainability, and clarity over structural overengineering.

Prefer modular code organization and split large files when they reduce readability, maintainability, or increase cognitive complexity. However, do not split blindly—each split must have a clear architectural reason such as separation of concerns, feature boundary, or reuse. Avoid over-fragmentation that makes the system harder to navigate or understand.

Split large files only when they become difficult to read, maintain, or reason about. Ensure each split reflects a clear responsibility, feature boundary, or abstraction. Do not split code arbitrarily or excessively, as over-fragmentation reduces clarity and maintainability.

Organize code into modular units to improve readability and maintainability. Split large files only when they violate clarity, separation of concerns, or become difficult to reason about. Ensure every split has a clear purpose and preserves architectural coherence. Avoid both oversized files and unnecessary fragmentation.

Identify and fix unnecessary, redundant, circular, bidirectional, or messy relationships. This is a mandatory review step, but do not make changes blindly. Carefully analyze the purpose, ownership, dependencies, and business meaning of each relationship before modifying it. Only apply changes when there is a clear justification and a measurable improvement in schema quality, maintainability, readability, or data integrity. Preserve all valid business requirements and explain the reasoning behind every relationship change.

Identify and fix unnecessary, redundant, circular, bidirectional, or messy relationships. Do NOT make changes blindly. Carefully analyze the business purpose, ownership, dependencies, and impact of each relationship before modifying it. Only apply changes when there is clear justification and an improvement in maintainability, readability, performance, or data integrity. Preserve valid business requirements and explain every relationship change.

Detect and fix unnecessary or messy relationships. Think carefully before making changes—never modify relationships blindly. Preserve valid business logic and only apply well-justified improvements that simplify the schema and improve maintainability.

Always present relational data in human-readable format in UI. Never expose raw IDs to users. Use IDs only for backend relationships. All relationship inputs must use controlled selection components (dropdown/autocomplete) to ensure valid foreign keys. APIs should return structured related objects, not isolated foreign keys.

MUST design UI/UX so users always understand what they are doing, what data they are entering, and the impact of their actions. Never expose raw IDs or database structures to users; always use human-readable labels and meaningful names. All relationships must be selected through guided controls (dropdowns, search, autocomplete, or visual selectors), never manually typed foreign keys. The UI must prevent blind data entry by providing clear field descriptions, validation, previews, and contextual guidance. Users should never need to understand database schema to use the system. The interface must guide decisions, prevent invalid input, and ensure data integrity through controlled inputs and clear feedback.

User wants speed and clarity, not information overload.

Design systems for speed and clarity over information completeness. Users should be able to complete tasks quickly and understand what is happening without cognitive overload or unnecessary context during primary actions.

Prioritize speed and clarity over information overload. Keep user flows minimal, focused, and easy to complete.

MUST design all user interfaces for speed, clarity, and task completion efficiency. Users should be able to finish actions with minimal steps and without cognitive overload. Avoid presenting unnecessary information during primary workflows (such as forms and actions). Only show essential fields and immediate context. Move extra information (history, reports, logs, analytics, advanced options) to separate views, tabs, or expandable sections. Ensure every screen has a clear purpose, a focused flow, and a simple path to completion.

Avoid hardcoded magic values. Use enums for predefined choices, constants for shared fixed values, and configuration or environment variables for configurable settings. Always use descriptive identifiers instead of raw literals to improve readability, maintainability, and consistency.

Use portable Laravel migration APIs.

Assume multiple actors can modify the same data at any time. Every write must be atomic, version-checked, and conflict-aware.

Assume things happen simultaneously; design so the result stays correct.

Concurrency = Multiple actors interacting with the same resources at overlapping times.

Concurrency = Multiple actors operating on the same system or shared data during overlapping periods of time. Therefore, never assume exclusive access or a guaranteed execution order.

Store every transaction with its original values and never modify historical records. Any later changes must be recorded separately and tracked through an audit trail. This ensures data integrity and accurate reporting.

Keep the implementation DRY and maintainable, but avoid overengineering.

Move tables to their correct owning module when clearly misplaced.

Split migrations that create or modify multiple unrelated tables.

Avoid generic migration frameworks, unnecessary abstractions and overengineering.

Merge later `Schema::table()` patch migrations into the original table-creation migration where safe.

Use one table per migration file.

Read recent records in /docs/changes before starting. Make only the required changes. After completion, create a new change record. Never modify previous records.

This project uses `/docs/changes` as its shared memory. Before starting any task, read the latest change records to understand recent work and avoid conflicts or duplicate changes. Implement only what is required for the task and keep changes minimal, without modifying unrelated or shared parts of the system. After finishing, add a new change record explaining what was changed and why. Never edit or delete existing records; they are an append-only history for future reference.

This project uses /docs/changes as shared memory. Before working, read recent records to understand context. Make only necessary changes and avoid touching unrelated or shared files. After finishing, add a new change record. Never modify or delete existing records.

In Laravel migrations, always prefer KISS + Explicit over DRY.

Migrations are database schema history, not application code. Never use loops, conditionals, or dynamic naming for foreign keys, columns, or constraints. Write every column, every foreign key, and every index explicitly—line by line, even if it repeats. This makes reviews instant, rollbacks safe, and debugging trivial. The tiny repetition you save is never worth the confusion of decoding dynamic logic inside a migration file. Future developers (including you) will thank you. Keep it stupidly simple and brutally explicit. Period.

Wise developers know when to follow the framework and when to break away. Be wise.

Evidence over guessing: If requirements are unclear or information is missing, ask for clarification instead of guessing.

Always fix issues in the layer where the root cause exists. Backend issues must be resolved in the backend, not hidden or worked around in the frontend. Core business logic, validations, rules, calculations, permissions, and data integrity enforcement must be implemented and enforced in the backend as the single source of truth. The frontend should focus on presentation, user interaction, and user experience, and may only duplicate validations for immediate feedback—not as the authoritative enforcement point.

Security first: Do not introduce security risks, expose secrets, bypass validation, or weaken authentication and authorization mechanisms.

Performance awareness: Consider performance implications, but do not sacrifice readability and maintainability for premature optimization.

Keep a single source of truth: Avoid duplicated logic, duplicated configuration, and duplicated business rules. Maintain a single source of truth whenever possible.

Explicit error handling: Handle errors explicitly and predictably. Do not silently ignore failures.

No dead code: Do not introduce unused code, unnecessary abstractions, speculative features, or placeholders for hypothetical future requirements.

Verify before completion: Before considering a task complete, verify that the solution satisfies all requirements and does not introduce regressions.

Before making changes, review the latest records in /docs/changes for context. Modify only the files necessary for the task and avoid unrelated changes. After completing the work, create a new record in /docs/changes describing what changed and why. Never edit or delete existing change records.

This project uses `/docs/changes` as its shared memory. Before starting any task, read the latest records to understand context and avoid repeating or conflicting work. Make only the minimal necessary changes to complete the task and do not modify unrelated or shared parts of the system. After finishing, add a new change record explaining what was changed and why. Never edit or delete existing records, as they form an append-only history for future reference.

Do not use hardcoded magic values. Store fixed values in appropriate abstractions such as enums, constants, configuration files, or environment variables based on their purpose. Use enums for predefined option sets, constants for shared immutable values, and configuration sources for environment-specific or frequently changing values. Always reference values through descriptive names rather than embedding raw literals in code.

Respect module ownership and separation of concerns. Fix problems at their source and keep responsibilities within the module that owns them. Do not compensate for bugs, missing functionality, or incomplete implementations in other modules. If logic is found in the wrong module, move it to the appropriate owner rather than expanding the current module's scope. Maintain clear boundaries, readability, and long-term maintainability.

Understand first, verify second, change third. Never guess, never overengineer, and never preserve a flawed design when a clean maintainable solution is feasible.
