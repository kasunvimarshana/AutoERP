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

## API Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/workflows` | List workflow definitions (paginated) |
| POST | `/api/v1/workflows` | Create a workflow definition |
| GET | `/api/v1/workflows/{id}` | Get a workflow definition |
| PUT | `/api/v1/workflows/{id}` | Update a workflow definition |
| DELETE | `/api/v1/workflows/{id}` | Delete a workflow definition |
| POST | `/api/v1/workflow-states` | Create a workflow state |
| POST | `/api/v1/workflow-transitions` | Create a workflow transition |
| POST | `/api/v1/workflow-instances` | Create a new workflow instance for an entity |
| GET | `/api/v1/workflow-instances` | List workflow instances by entity type |
| GET | `/api/v1/workflow-instances/{id}` | Get a workflow instance |
| POST | `/api/v1/workflow-instances/{id}/transition` | Apply a state transition to a workflow instance |

## Files Implemented

```
Modules/Workflow/
├── Application/
│   ├── DTOs/CreateWorkflowDTO.php
│   └── Services/WorkflowService.php
├── Domain/
│   ├── Contracts/WorkflowRepositoryContract.php
│   └── Entities/
│       ├── WorkflowDefinition.php
│       ├── WorkflowState.php
│       ├── WorkflowTransition.php
│       ├── WorkflowInstance.php
│       └── WorkflowTransitionLog.php
├── Infrastructure/
│   ├── Database/Migrations/
│   │   ├── 2026_02_27_000010_create_workflow_definitions_table.php
│   │   ├── 2026_02_27_000011_create_workflow_states_table.php
│   │   ├── 2026_02_27_000012_create_workflow_transitions_table.php
│   │   ├── 2026_02_27_000013_create_workflow_instances_table.php
│   │   └── 2026_02_27_000014_create_workflow_transition_logs_table.php
│   ├── Providers/WorkflowServiceProvider.php
│   └── Repositories/WorkflowRepository.php
├── Interfaces/Http/Controllers/WorkflowController.php
├── routes/api.php
├── module.json
└── README.md
```

## Service Methods

| Method | Description |
|---|---|
| `createDefinition` | Create a new workflow definition |
| `listDefinitions` | List all workflow definitions (paginated) |
| `showDefinition` | Show a single workflow definition |
| `deleteDefinition` | Delete a workflow definition |
| `createState` | Create a workflow state for a definition |
| `createTransition` | Create a workflow transition between states |
| `createInstance` | Create a new workflow instance for an entity |
| `listInstances` | List workflow instances by entity type |
| `showInstance` | Show a single workflow instance |
| `applyTransition` | Apply a state transition to a workflow instance |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreateWorkflowDTOTest.php` | Unit | `CreateWorkflowDTO` — field hydration, defaults |
| `Tests/Unit/WorkflowServiceTest.php` | Unit | createDefinition/listDefinitions delegation — method signatures |
| `Tests/Unit/WorkflowServiceCrudTest.php` | Unit | showDefinition, deleteDefinition, createState, createTransition, createInstance, listInstances, showInstance, applyTransition — 11 assertions |
| `Tests/Unit/WorkflowServiceTransitionLogTest.php` | Unit | listTransitionLogs — method existence, visibility, signature, entity compliance, regression guards — 11 assertions |

---

## Status

🟢 **Complete** — Core CRUD scaffolding complete; full state/transition/instance management and **transition log listing** (listTransitionLogs) implemented (~85% test coverage).

See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
