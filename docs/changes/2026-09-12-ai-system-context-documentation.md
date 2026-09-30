# AI system context documentation

Date: 2026-09-12

## Request

Create one comprehensive Markdown guide that gives a future AI model enough verified context to understand AutoERP's business logic, technical stack, architecture, module ownership, critical workflows, and development rules before changing the system.

## Change

Added `docs/AI_SYSTEM_CONTEXT.md` as a maintained system-level orientation document.

It records:

- the Laravel modular-monolith and React SPA stack;
- repository structure and request layering;
- tenant, organization-unit, authentication, permission, and API boundaries;
- concurrency, versioning, exact-decimal, idempotency, snapshot, and reversal rules;
- each implemented business module and its ownership;
- normal procurement, Invoice, Payment, Finance, Inventory, Vehicle Service, Vehicle Rental, and WhatsApp workflows;
- the distinction between implemented behavior and the future Expenses and consignment requirements;
- frontend, migration, testing, and AI change protocols;
- high-risk mistakes and a task-to-module orientation table.

No runtime application behavior, schema, configuration, or transaction data was changed by this documentation task.

## Verification

- Compared the guide against `composer.json`, `package.json`, application bootstrap/providers, module models/services/routes, frontend router/API client, lifecycle enums, runtime configuration, and recent change records.
- Confirmed the document distinguishes approved future requirements from implemented features.
- Confirmed the new files pass `git diff --check`.
