# Workflow Module

Complete workflow automation module with approval chains, conditional routing, and parallel execution.

## Features

- **Workflow Definitions**: Create reusable workflow templates with steps and conditions
- **Step Types**: Start, Action, Approval, Condition, Parallel, End
- **Action Types**: Create/Update/Delete records, Send notifications/emails, Webhooks, Wait
- **Conditional Routing**: Route workflow based on conditions (if-then-else logic)
- **Parallel Execution**: Execute multiple steps simultaneously
- **Approval Chains**: Multi-level approvals with escalation and delegation
- **Instance Tracking**: Complete audit trail of workflow executions
- **Event-Driven**: Rich events for integration with other modules
- **Metadata-Driven**: Fully configurable through metadata

## Components

### Enums
- `WorkflowStatus`: draft, active, inactive, archived
- `StepType`: start, action, approval, condition, parallel, end
- `ApprovalStatus`: pending, approved, rejected, delegated, cancelled
- `ConditionType`: equals, not_equals, greater_than, less_than, contains, in_array, regex, custom
- `ActionType`: create_record, update_record, delete_record, send_notification, send_email, webhook, script, wait
- `InstanceStatus`: pending, running, waiting, completed, failed, cancelled

### Models
- `Workflow`: Workflow definitions
- `WorkflowStep`: Individual steps in a workflow
- `WorkflowCondition`: Conditional routing rules
- `WorkflowInstance`: Execution instances
- `WorkflowInstanceStep`: Step execution tracking
- `Approval`: Approval requests

### Services
- `WorkflowEngine`: Core workflow execution engine
- `WorkflowExecutor`: Action and condition execution
- `WorkflowBuilder`: Build and validate workflow definitions
- `ApprovalService`: Approval chain management

### Controllers
- `WorkflowController`: Workflow CRUD and execution
- `WorkflowInstanceController`: Instance management
- `ApprovalController`: Approval actions

## API Endpoints

### Workflows
```
GET    /api/v1/workflows                  - List workflows
POST   /api/v1/workflows                  - Create workflow
GET    /api/v1/workflows/{id}             - Get workflow
PUT    /api/v1/workflows/{id}             - Update workflow
DELETE /api/v1/workflows/{id}             - Delete workflow
POST   /api/v1/workflows/{id}/execute     - Execute workflow
POST   /api/v1/workflows/{id}/activate    - Activate workflow
POST   /api/v1/workflows/{id}/deactivate  - Deactivate workflow
POST   /api/v1/workflows/{id}/duplicate   - Duplicate workflow
```

### Workflow Instances
```
GET    /api/v1/workflow-instances             - List instances
GET    /api/v1/workflow-instances/{id}        - Get instance
POST   /api/v1/workflow-instances/{id}/cancel - Cancel instance
POST   /api/v1/workflow-instances/{id}/resume - Resume instance
```

### Approvals
```
GET    /api/v1/approvals              - List approvals
GET    /api/v1/approvals/pending      - Get pending approvals
GET    /api/v1/approvals/{id}         - Get approval
POST   /api/v1/approvals/{id}/respond - Approve/Reject
POST   /api/v1/approvals/{id}/delegate - Delegate approval
```

## Usage Examples

### Create a Simple Workflow
```php
$workflow = $workflowBuilder->create([
    'name' => 'Purchase Order Approval',
    'trigger_type' => 'manual',
    'steps' => [
        [
            'name' => 'Start',
            'type' => 'start',
            'sequence' => 1,
        ],
        [
            'name' => 'Manager Approval',
            'type' => 'approval',
            'sequence' => 2,
            'approval_config' => [
                'approver_id' => $managerId,
                'due_hours' => 24,
                'priority' => 1,
            ],
        ],
        [
            'name' => 'End',
            'type' => 'end',
            'sequence' => 3,
        ],
    ],
]);
```

### Execute Workflow
```php
$instance = $workflowEngine->start($workflow, [
    'entity_type' => 'purchase_order',
    'entity_id' => $purchaseOrder->id,
    'amount' => $purchaseOrder->total,
]);
```

### Conditional Routing
```php
[
    'name' => 'Check Amount',
    'type' => 'condition',
    'sequence' => 2,
    'conditions' => [
        [
            'type' => 'greater_than',
            'field' => 'amount',
            'value' => 10000,
            'next_step_id' => $directorApprovalStepId,
        ],
        [
            'is_default' => true,
            'next_step_id' => $managerApprovalStepId,
        ],
    ],
]
```

### Approve/Reject
```php
$approvalService->approve($approval, [
    'comments' => 'Approved',
]);

$approvalService->reject($approval, [
    'comments' => 'Budget exceeded',
]);
```

## Events

- `WorkflowCreated` - New workflow created
- `WorkflowUpdated` - Workflow updated
- `WorkflowInstanceStarted` - Instance started
- `WorkflowInstanceCompleted` - Instance completed
- `WorkflowInstanceFailed` - Instance failed
- `WorkflowStepStarted` - Step started
- `WorkflowStepCompleted` - Step completed
- `WorkflowActionExecuted` - Action executed
- `ApprovalCreated` - Approval requested
- `ApprovalResponded` - Approval decision made

## Configuration

Config file: `modules/Workflow/Config/workflow.php`

Environment variables:
```
WORKFLOW_DEFAULT_TIMEOUT=300
WORKFLOW_MAX_RETRY_COUNT=3
WORKFLOW_APPROVAL_DUE_HOURS=24
WORKFLOW_ESCALATION_CHECK_INTERVAL=3600
```

## Integration

The Workflow module integrates with:
- **Notification**: Send notifications on workflow events
- **Audit**: Comprehensive audit logging
- **All Business Modules**: Automate processes across modules

## Architecture

- **Stateless Execution**: Each step execution is atomic and isolated
- **Event-Driven**: Rich event system for extensibility
- **Metadata-Driven**: Workflows configured through metadata, not code
- **Concurrent-Safe**: Handles concurrent workflow executions safely
- **Transaction-Safe**: Database transactions ensure data integrity
- **Retry-Safe**: Steps can be safely retried on failure

## Security

- Policy-based authorization
- Tenant isolation
- Approval authorization validation
- Audit trail for all actions

## Performance

- Efficient step execution
- Parallel step support
- Queue-ready for async processing
- Optimized database queries

---

**Status**: ✅ PRODUCTION-READY - Last remaining module complete!
