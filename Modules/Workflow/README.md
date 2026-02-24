# Workflow Module

## Overview

The Workflow module provides a configurable state-machine engine for all document types. Workflow definitions (states, allowed transitions) are stored in the database — not hardcoded — enabling administrators to configure approval chains and lifecycle rules per document type without code changes.

---

## Features

- **Workflow definitions** – DB-stored states and transitions per document type; active/inactive lifecycle
- **Transition validation** – Guard checks that a transition from→to exists in the allowed transitions list before recording it
- **Workflow history** – Immutable audit trail of every state transition (who, when, from, to, comment)
- **Domain events** – `WorkflowCreated`, `WorkflowTransitioned`
- **Tenant isolation** – Global tenant scope via `HasTenantScope` trait

---

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/workflows` | List tenant workflows |
| POST | `api/v1/workflows` | Create workflow definition |
| GET | `api/v1/workflows/{id}` | Get workflow |
| PUT | `api/v1/workflows/{id}` | Update workflow |
| DELETE | `api/v1/workflows/{id}` | Delete workflow |
| POST | `api/v1/workflows/{id}/transition` | Record a state transition |
| GET | `api/v1/workflows/{id}/history` | Get transition history |

---

## Domain Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `WorkflowCreated` | `workflowId`, `tenantId`, `name`, `documentType` | New workflow created |
| `WorkflowTransitioned` | `workflowId`, `tenantId`, `documentType`, `documentId`, `fromState`, `toState`, `actorId` | State transition recorded |

---

## Architecture

- **Domain**: `WorkflowStatus` enum, `ApprovalChainType` enum, `WorkflowRepositoryInterface`, `WorkflowHistoryRepositoryInterface`, domain events
- **Application**: `CreateWorkflowUseCase` (states validation), `TransitionWorkflowUseCase` (transition guard)
- **Infrastructure**: `WorkflowModel` (SoftDeletes + HasTenantScope), `WorkflowHistoryModel` (immutable), migrations, repositories
- **Presentation**: `WorkflowController`, `StoreWorkflowRequest`, `TransitionWorkflowRequest`
