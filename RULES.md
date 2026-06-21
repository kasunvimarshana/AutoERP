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

Evidence over guessing: If requirements are unclear or information is missing, ask for clarification instead of guessing.

Support both vertical (feature-based) and horizontal (cross-cutting) development where appropriate. Design changes should enable feature enhancement without breaking existing functionality. Maintain data integrity at all times. Ensure loose coupling and high cohesion between modules. Follow clean architecture principles with clear separation of concerns, and respect module ownership boundaries. Prefer scalable and maintainable designs over tightly coupled or rigid implementations.

Support vertical feature development and necessary horizontal cross-cutting improvements without violating module boundaries. Ensure all changes preserve data integrity, maintain loose coupling, and follow clean architecture principles. Enhance features in a way that does not introduce unnecessary dependencies or architectural complexity. Prioritize scalability, maintainability, and clarity over structural overengineering.

Prefer modular code organization and split large files when they reduce readability, maintainability, or increase cognitive complexity. However, do not split blindly—each split must have a clear architectural reason such as separation of concerns, feature boundary, or reuse. Avoid over-fragmentation that makes the system harder to navigate or understand.

Split large files only when they become difficult to read, maintain, or reason about. Ensure each split reflects a clear responsibility, feature boundary, or abstraction. Do not split code arbitrarily or excessively, as over-fragmentation reduces clarity and maintainability.

Organize code into modular units to improve readability and maintainability. Split large files only when they violate clarity, separation of concerns, or become difficult to reason about. Ensure every split has a clear purpose and preserves architectural coherence. Avoid both oversized files and unnecessary fragmentation.

Security first: Do not introduce security risks, expose secrets, bypass validation, or weaken authentication and authorization mechanisms.

Performance awareness: Consider performance implications, but do not sacrifice readability and maintainability for premature optimization.

Keep a single source of truth: Avoid duplicated logic, duplicated configuration, and duplicated business rules. Maintain a single source of truth whenever possible.

Explicit error handling: Handle errors explicitly and predictably. Do not silently ignore failures.

No dead code: Do not introduce unused code, unnecessary abstractions, speculative features, or placeholders for hypothetical future requirements.

Verify before completion: Before considering a task complete, verify that the solution satisfies all requirements and does not introduce regressions.

Respect module ownership and separation of concerns. Fix problems at their source and keep responsibilities within the module that owns them. Do not compensate for bugs, missing functionality, or incomplete implementations in other modules. If logic is found in the wrong module, move it to the appropriate owner rather than expanding the current module's scope. Maintain clear boundaries, readability, and long-term maintainability.

Understand first, verify second, change third. Never guess, never overengineer, and never preserve a flawed design when a clean maintainable solution is feasible.
