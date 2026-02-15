<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SalesOrder $order,
        private readonly string $status,
        private readonly ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusMessages = [
            'pending' => 'Order is pending approval',
            'approved' => 'Order has been approved',
            'processing' => 'Order is being processed',
            'fulfilled' => 'Order has been fulfilled',
            'completed' => 'Order has been completed',
            'cancelled' => 'Order has been cancelled',
        ];

        $severity = match ($this->status) {
            'approved', 'completed' => 'success',
            'cancelled' => 'error',
            'pending' => 'warning',
            default => 'info',
        };

        return [
            'type' => 'order_status',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->status,
            'customer_name' => $this->order->customer?->name ?? 'Unknown',
            'total_amount' => $this->order->total_amount,
            'currency_code' => $this->order->currency_code,
            'title' => 'Order Status Update',
            'message' => "Order #{$this->order->order_number}: " . ($statusMessages[$this->status] ?? "Status changed to {$this->status}") . ($this->notes ? " - {$this->notes}" : ''),
            'action_url' => "/sales/orders/{$this->order->id}",
            'severity' => $severity,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
