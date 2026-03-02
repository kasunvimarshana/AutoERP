# Workflow Module

## Overview

The **Workflow** module provides a database-driven state machine engine for approval chains, business process flows, and event-based automation. No hardcoded approval logic is permitted.

## State Machine Model

```
State → Event → Transition → Guard → Action
```

---

## Responsibilities

- Workflow definition (states, events, transitions, guards, actions)
- Approval chain configuration
- Escalation rules and timers
- SLA enforcement
- Event-based trigger processing
- Background job scheduling
- Transition history (immutable audit trail)

---

## Architecture Layer

```
Modules/Workflow/
 ├── Application/       # Start/advance/reject workflow use cases
 ├── Domain/            # WorkflowDefinition, State, Transition entities, repository contracts
 ├── Infrastructure/    # Repository implementations, WorkflowServiceProvider, state machine engine
 ├── Interfaces/        # Controllers, API resources, form requests
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`
- `metadata`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| No hardcoded approval logic | ✅ Enforced |
| All workflow states and transitions database-driven | ✅ Required |
| Immutable transition history (audit trail) | ✅ Enforced |
| Tenant-scoped workflows | ✅ Enforced |
| No cross-module coupling | ✅ Enforced |

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
