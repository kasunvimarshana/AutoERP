# Notification Module Services Layer

This document describes the Services layer implementation for the Notification module.

## Overview

The Notification module provides a complete notification system supporting multiple channels (Email, SMS, Push, In-App) with template management, scheduling, and retry mechanisms.

## Architecture

The Services layer follows the **Clean Architecture** and **Repository Pattern** established in the platform:

- **Service Layer**: Business logic and orchestration
- **Repository Layer**: Data access abstraction
- **Events**: Decoupled notification of important actions
- **Transactions**: All mutations wrapped in database transactions
- **Native Laravel**: Uses only native Laravel features (Mail, Events, Queues)

## Services

### 1. NotificationService

**Main orchestration service** for sending and managing notifications.

#### Methods

- `send(userId, templateCode, data, type, priority)` - Send a notification to a user
- `sendBulk(userIds, templateCode, data, type, priority)` - Send to multiple users
- `markAsRead(notificationId)` - Mark notification as read
- `markAllAsRead(userId)` - Mark all notifications as read for a user
- `delete(notificationId)` - Delete a notification
- `schedule(userId, templateCode, data, scheduledAt, type, priority)` - Schedule future delivery
- `getUnreadCount(userId)` - Get unread count for a user
- `retry(notificationId)` - Retry a failed notification

#### Usage Example

```php
use Modules\Notification\Services\NotificationService;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Enums\NotificationPriority;

$notificationService = app(NotificationService::class);

// Send a notification
$notification = $notificationService->send(
    userId: 123,
    templateCode: 'order_confirmed',
    data: [
        'order_number' => 'ORD-20240101-000001',
        'customer_name' => 'John Doe',
        'total_amount' => '$1,234.56',
    ],
    type: NotificationType::EMAIL,
    priority: NotificationPriority::HIGH
);

// Mark as read
$notificationService->markAsRead($notification->id);

// Schedule for later
$scheduled = $notificationService->schedule(
    userId: 123,
    templateCode: 'payment_reminder',
    data: ['invoice_number' => 'INV-001'],
    scheduledAt: now()->addDays(7)
);
```

### 2. TemplateService

**Template management service** for creating, updating, and rendering notification templates.

#### Methods

- `render(templateCode, data)` - Render template with data
- `validate(templateCode, data)` - Validate data against template requirements
- `create(data)` - Create a new template
- `update(templateId, data)` - Update existing template
- `delete(templateId)` - Delete a template
- `toggleActive(templateId, isActive)` - Activate/deactivate template
- `preview(templateCode, data)` - Preview rendering without creating notification

#### Usage Example

```php
use Modules\Notification\Services\TemplateService;

$templateService = app(TemplateService::class);

// Create a template
$template = $templateService->create([
    'code' => 'welcome_email',
    'name' => 'Welcome Email',
    'type' => 'email',
    'subject' => 'Welcome {{name}}!',
    'body_text' => 'Hello {{name}}, welcome to our platform!',
    'body_html' => '<h1>Hello {{name}}</h1><p>Welcome to our platform!</p>',
    'variables' => [
        ['name' => 'name', 'type' => 'string', 'required' => true],
    ],
]);

// Preview template
$preview = $templateService->preview('welcome_email', [
    'name' => 'John Doe',
]);
```

### 3. NotificationDispatcher

**Channel routing service** that dispatches notifications to appropriate channel services.

#### Methods

- `dispatch(notification)` - Dispatch notification to appropriate channel
- `retry(notification)` - Retry a failed notification
- `dispatchBatch(notifications)` - Batch dispatch multiple notifications
- `processPending(limit)` - Process pending notifications
- `processRetries()` - Process retryable failed notifications

#### Features

- Automatic channel routing based on notification type
- Retry mechanism with exponential backoff
- Status tracking (pending → sent/failed)
- Comprehensive logging

#### Usage Example

```php
use Modules\Notification\Services\NotificationDispatcher;

$dispatcher = app(NotificationDispatcher::class);

// Dispatch single notification
$success = $dispatcher->dispatch($notification);

// Process pending notifications (useful for queue workers)
$results = $dispatcher->processPending(100);

// Retry failed notifications
$retryResults = $dispatcher->processRetries();
```

### 4. EmailNotificationService

**Email channel implementation** using native Laravel Mail.

#### Features

- Uses Laravel Mail facade
- Supports HTML email bodies
- Priority handling (urgent/high → priority 1)
- Comprehensive logging to NotificationLog
- Error handling with detailed messages

#### Configuration

Uses standard Laravel mail configuration from `config/mail.php` and `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. SmsNotificationService

**SMS channel placeholder** for future SMS provider integration.

#### Current Implementation

- Mock implementation that logs success without actually sending
- Ready for integration with providers like Twilio, AWS SNS, etc.
- Logs all attempts to NotificationLog

#### Integration Notes

To integrate a real SMS provider:

1. Install provider SDK (e.g., Twilio)
2. Update `send()` method to call provider API
3. Update configuration in `config/notification.php`
4. Add provider credentials to `.env`

### 6. PushNotificationService

**Push notification placeholder** for mobile push notifications.

#### Current Implementation

- Mock implementation that logs success without actually sending
- Ready for integration with FCM, APNS, or other push services
- Logs all attempts to NotificationLog

#### Integration Notes

To integrate push notifications:

1. Install provider SDK (e.g., Firebase Cloud Messaging)
2. Update `send()` method to call provider API
3. Implement device token management
4. Update configuration

### 7. InAppNotificationService

**In-app notification handler** for notifications stored in database.

#### Features

- Notifications are already in database when created
- Marks notifications as sent/delivered immediately
- Logs delivery to NotificationLog
- Supports real-time updates via Laravel Echo/Pusher

## Events

The services fire the following events:

### NotificationSent

Fired when a notification is successfully sent.

```php
event(new NotificationSent($notification));
```

### NotificationFailed

Fired when a notification fails to send.

```php
event(new NotificationFailed($notification, $errorMessage));
```

### NotificationRead

Fired when a notification is marked as read.

```php
event(new NotificationRead($notification));
```

## Configuration

Configuration is stored in `modules/Notification/Config/notification.php`:

```php
return [
    'max_retries' => env('NOTIFICATION_MAX_RETRIES', 3),
    'retry_delay' => env('NOTIFICATION_RETRY_DELAY', 300), // seconds
    'cleanup_days' => env('NOTIFICATION_CLEANUP_DAYS', 90),
    'channels' => [
        'email' => ['enabled' => true],
        'sms' => ['enabled' => false, 'provider' => 'mock'],
        'push' => ['enabled' => false, 'provider' => 'mock'],
        'in_app' => ['enabled' => true],
    ],
];
```

## Database Transactions

All mutation operations are wrapped in database transactions using `TransactionHelper::execute()`:

- Automatic retry on deadlock (up to 3 attempts)
- Exponential backoff
- Rollback on any exception

## Error Handling

Services throw appropriate exceptions:

- `NotificationNotFoundException` - Notification not found
- `NotificationTemplateNotFoundException` - Template not found
- `InvalidTemplateDataException` - Invalid template data
- `NotificationSendFailedException` - Failed to send notification

All exceptions extend `Modules\Core\Exceptions\BaseException`.

## Logging

All notification attempts are logged to the `notification_logs` table:

- Notification ID
- User ID
- Channel
- Status (sent/failed)
- Recipient
- Timestamps
- Error messages
- Metadata

## Queue Integration

For production use, notifications should be queued:

```php
use Illuminate\Support\Facades\Queue;

Queue::push(function () use ($notification) {
    app(NotificationDispatcher::class)->dispatch($notification);
});
```

Or use Laravel's native notification system as a wrapper.

## Scheduled Notifications

Notifications can be scheduled for future delivery:

```php
$notification = $notificationService->schedule(
    userId: 123,
    templateCode: 'reminder',
    data: ['message' => 'Don\'t forget!'],
    scheduledAt: now()->addHours(24)
);
```

A scheduled job/command should process pending notifications:

```php
// In a scheduled command
$dispatcher->processPending(100);
```

## Best Practices

1. **Always use templates** - Create reusable templates instead of hardcoding content
2. **Validate data** - Always validate template data before sending
3. **Handle failures gracefully** - Implement retry logic and monitor failed notifications
4. **Use appropriate priorities** - Reserve URGENT for critical notifications
5. **Clean up old notifications** - Implement periodic cleanup of old/read notifications
6. **Monitor logs** - Regularly review notification logs for delivery issues
7. **Test with mock services** - SMS and Push use mock services by default for testing

## Testing

Services can be tested using Laravel's testing utilities:

```php
use Modules\Notification\Services\NotificationService;
use Modules\Notification\Enums\NotificationType;

public function test_can_send_notification()
{
    $service = app(NotificationService::class);
    
    $notification = $service->send(
        userId: $this->user->id,
        templateCode: 'test_template',
        data: ['name' => 'Test User']
    );
    
    $this->assertNotNull($notification);
    $this->assertEquals(NotificationType::EMAIL, $notification->type);
}
```

## Integration with Other Modules

Notifications can be triggered from any module:

```php
// In Sales module after order creation
app(NotificationService::class)->send(
    userId: $order->customer->user_id,
    templateCode: 'order_confirmed',
    data: [
        'order_number' => $order->order_code,
        'total' => $order->total_amount,
    ]
);

// In CRM module after lead conversion
app(NotificationService::class)->sendBulk(
    userIds: $salesTeam->pluck('id')->toArray(),
    templateCode: 'lead_converted',
    data: ['lead_name' => $lead->name]
);
```

## Security Considerations

- All operations are tenant-scoped
- User can only read/delete their own notifications
- Template validation prevents injection attacks
- Email bodies are sanitized before sending
- Failed notifications include error details but not sensitive data

## Performance Considerations

- Bulk operations process users individually to prevent memory issues
- Notifications should be queued for large batches
- Database indexes on user_id, status, and scheduled_at for fast queries
- Old notifications should be archived/deleted periodically
- Implement rate limiting for notification sending

## Future Enhancements

- Real-time notifications via WebSockets/Pusher
- SMS provider integration (Twilio, AWS SNS)
- Push notification integration (FCM, APNS)
- Slack/Teams webhook integration
- Rich notification templates with attachments
- A/B testing for notification templates
- Analytics and delivery tracking
- User notification preferences
