<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Core\Events\BaseEvent;
use App\Models\User;
use App\Notifications\EventNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Notification Service
 * 
 * Central service for managing notifications across the application
 */
class NotificationService
{
    /**
     * Send notification based on domain event
     */
    public function sendEventNotification(BaseEvent $event): void
    {
        $eventName = $event->getEventName();
        
        // Get users who should receive this notification
        $users = $this->getUsersForEvent($event);
        
        if ($users->isEmpty()) {
            Log::debug('No users to notify for event', [
                'event' => $eventName,
                'tenant_id' => $event->tenantId,
            ]);
            return;
        }

        // Create notification data
        $notificationData = $this->buildNotificationData($event);
        
        // Send notification to users
        foreach ($users as $user) {
            // Check user notification preferences
            if ($this->shouldNotifyUser($user, $eventName)) {
                $user->notify(new EventNotification(
                    $notificationData['title'],
                    $notificationData['message'],
                    $notificationData['type'],
                    $notificationData['action_url'],
                    $notificationData['metadata']
                ));
            }
        }
    }

    /**
     * Get users who should receive notification for this event
     */
    private function getUsersForEvent(BaseEvent $event): \Illuminate\Support\Collection
    {
        $eventName = $event->getEventName();
        
        // Map events to permissions
        $permission = match (true) {
            str_contains($eventName, 'Stock') => 'receive-inventory-alerts',
            str_contains($eventName, 'Order') => 'receive-sales-alerts',
            str_contains($eventName, 'Invoice') => 'receive-invoice-alerts',
            str_contains($eventName, 'Payment') => 'receive-payment-alerts',
            str_contains($eventName, 'PurchaseOrder') => 'receive-procurement-alerts',
            str_contains($eventName, 'WorkOrder') => 'receive-manufacturing-alerts',
            str_contains($eventName, 'Transfer') || str_contains($eventName, 'Picking') || str_contains($eventName, 'Putaway') => 'receive-warehouse-alerts',
            str_contains($eventName, 'Lead') || str_contains($eventName, 'Customer') => 'receive-crm-alerts',
            default => null,
        };

        if (!$permission) {
            return collect();
        }

        // Get users with permission for this tenant
        return User::where('tenant_id', $event->tenantId)
            ->permission($permission)
            ->get();
    }

    /**
     * Build notification data from event
     */
    private function buildNotificationData(BaseEvent $event): array
    {
        $eventName = $event->getEventName();
        $eventData = $event->toArray();
        
        // Default values
        $title = $eventName;
        $message = 'An event occurred in the system';
        $type = 'info';
        $actionUrl = null;
        
        // Customize based on event type
        match (true) {
            str_contains($eventName, 'LowStockDetected') => [
                'title' => $title = 'Low Stock Alert',
                'message' => $message = "Product {$eventData['product_name']} is running low on stock",
                'type' => $type = 'warning',
                'actionUrl' => $actionUrl = "/inventory/products/{$eventData['product_id']}",
            ],
            str_contains($eventName, 'StockExpiring') => [
                'title' => $title = 'Stock Expiring Soon',
                'message' => $message = "Product {$eventData['product_name']} has stock expiring soon",
                'type' => $type = 'warning',
                'actionUrl' => $actionUrl = "/inventory/expiring",
            ],
            str_contains($eventName, 'OrderCreated') => [
                'title' => $title = 'New Order Created',
                'message' => $message = "Order {$eventData['order_number']} has been created",
                'type' => $type = 'success',
                'actionUrl' => $actionUrl = "/sales/orders/{$eventData['order_id']}",
            ],
            str_contains($eventName, 'InvoiceOverdue') => [
                'title' => $title = 'Invoice Overdue',
                'message' => $message = "Invoice {$eventData['invoice_number']} is overdue",
                'type' => $type = 'error',
                'actionUrl' => $actionUrl = "/invoices/{$eventData['invoice_id']}",
            ],
            str_contains($eventName, 'PaymentReceived') => [
                'title' => $title = 'Payment Received',
                'message' => $message = "Payment of {$eventData['amount']} received from {$eventData['customer_name']}",
                'type' => $type = 'success',
                'actionUrl' => $actionUrl = "/payments/{$eventData['payment_id']}",
            ],
            default => null,
        };

        return [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
            'metadata' => $eventData,
        ];
    }

    /**
     * Check if user should receive notification based on preferences
     */
    private function shouldNotifyUser(User $user, string $eventName): bool
    {
        // Check user notification preferences
        // This can be extended with a user_notification_preferences table
        $preferences = $user->notification_preferences ?? [];
        
        // If no preferences set, default to true
        if (empty($preferences)) {
            return true;
        }

        // Check specific event preference
        return $preferences[$eventName] ?? $preferences['all'] ?? true;
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotification(
        array $userIds,
        string $title,
        string $message,
        string $type = 'info',
        ?string $actionUrl = null,
        array $metadata = []
    ): void {
        $users = User::whereIn('id', $userIds)->get();
        
        Notification::send(
            $users,
            new EventNotification($title, $message, $type, $actionUrl, $metadata)
        );
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId, int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        
        return true;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        $user->unreadNotifications->markAsRead();
        
        return true;
    }

    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount(int $userId): int
    {
        $user = User::find($userId);
        
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    /**
     * Get notifications for user
     */
    public function getNotifications(int $userId, int $limit = 50, bool $unreadOnly = false): \Illuminate\Support\Collection
    {
        $user = User::find($userId);
        
        if (!$user) {
            return collect();
        }

        $query = $unreadOnly ? $user->unreadNotifications() : $user->notifications();
        
        return $query->limit($limit)->get();
    }
}
