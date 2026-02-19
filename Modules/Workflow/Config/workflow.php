<?php

declare(strict_types=1);

return [
    'default_timeout' => env('WORKFLOW_DEFAULT_TIMEOUT', 300),
    'max_retry_count' => env('WORKFLOW_MAX_RETRY_COUNT', 3),
    'approval_due_hours' => env('WORKFLOW_APPROVAL_DUE_HOURS', 24),
    'escalation_check_interval' => env('WORKFLOW_ESCALATION_CHECK_INTERVAL', 3600),

    'triggers' => [
        'manual' => 'Manual Trigger',
        'scheduled' => 'Scheduled Trigger',
        'event' => 'Event-Based Trigger',
        'webhook' => 'Webhook Trigger',
    ],

    'action_types' => [
        'create_record' => 'Create Record',
        'update_record' => 'Update Record',
        'delete_record' => 'Delete Record',
        'send_notification' => 'Send Notification',
        'send_email' => 'Send Email',
        'webhook' => 'Call Webhook',
        'wait' => 'Wait',
    ],

    'step_types' => [
        'start' => 'Start Step',
        'action' => 'Action Step',
        'approval' => 'Approval Step',
        'condition' => 'Condition Step',
        'parallel' => 'Parallel Step',
        'end' => 'End Step',
    ],

    'condition_types' => [
        'equals' => 'Equals',
        'not_equals' => 'Not Equals',
        'greater_than' => 'Greater Than',
        'less_than' => 'Less Than',
        'contains' => 'Contains',
        'in_array' => 'In Array',
        'regex' => 'Regular Expression',
    ],
];
