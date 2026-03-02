# Workflow Module

Database-driven state machine workflow engine for the Enterprise ERP/CRM SaaS platform.

## Architecture Pattern

This module follows the standard layered architecture:

```
Controller → Service → Handler (Pipeline) → Repository → Entity
```

- **Domain** – Pure PHP entities (`WorkflowDefinition`, `WorkflowState`, `WorkflowTransition`, `WorkflowInstance`, `WorkflowInstanceLog`) and repository contracts
- **Application** – Commands and handlers (write operations use `ValidateCommandPipe → AuditLogPipe` pipeline), services
- **Infrastructure** – Eloquent models, migrations, repository implementations
- **Interfaces** – HTTP controllers, form request validators, JSON resources, routes

## API Endpoints

### Workflow Definitions

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/workflows` | List all definitions (paginated) |
| POST | `/api/v1/workflows` | Create a new workflow definition |
| GET | `/api/v1/workflows/{id}` | Get a specific definition |
| PUT | `/api/v1/workflows/{id}` | Update a definition |
| DELETE | `/api/v1/workflows/{id}` | Soft-delete a definition |
| GET | `/api/v1/workflows/{id}/states` | List states for a definition |
| GET | `/api/v1/workflows/{id}/transitions` | List transitions for a definition |

### Workflow Instances

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/workflow-instances` | List all instances (paginated) |
| POST | `/api/v1/workflow-instances` | Start a new workflow instance |
| GET | `/api/v1/workflow-instances/{id}` | Get a specific instance |
| POST | `/api/v1/workflow-instances/{id}/advance` | Advance instance via transition |
| POST | `/api/v1/workflow-instances/{id}/cancel` | Cancel an active instance |
| DELETE | `/api/v1/workflow-instances/{id}` | Soft-delete an instance |
| GET | `/api/v1/workflow-instances/{id}/logs` | Get audit log for instance |

## Workflow Lifecycle

```
[Start] → [Initial State] → [State A] → [State B] → [Final State] → [Completed]
                          ↗          ↘
                    Transition      Transition
```

1. Create a `WorkflowDefinition` with at least 2 states and transitions between them
2. Mark exactly one state as `is_initial: true` and one or more as `is_final: true`
3. Start a `WorkflowInstance` for any entity (entity_type + entity_id) — places it at the initial state
4. Advance the instance by specifying a valid `transition_id` — the system validates the from-state matches the current state
5. When advancing to a final state, the instance status automatically becomes `completed`
6. Instances can be cancelled at any time while `active`

## States & Transitions Design

- Each `WorkflowDefinition` owns multiple `WorkflowState` records
- A `WorkflowTransition` links a `from_state_id` to a `to_state_id` within the same definition
- Transitions can require a comment (`requires_comment: true`) for approval/rejection workflows
- States have a `sort_order` for display ordering

## Approval Chain Design

To build an approval chain:

1. Create states: `draft`, `pending_review`, `approved`, `rejected`
2. Create transitions:
   - `submit` (draft → pending_review)
   - `approve` (pending_review → approved, requires_comment: false)
   - `reject` (pending_review → rejected, requires_comment: true)
   - `resubmit` (rejected → pending_review)
3. Mark `approved` and `rejected` as `is_final: true`
4. Start instances for documents/requests and advance through the chain via the API
