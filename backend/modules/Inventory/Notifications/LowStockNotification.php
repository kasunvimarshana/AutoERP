<?php

namespace Modules\Inventory\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Inventory\Models\Product;

class LowStockNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Product $product,
        public string $warehouseId,
        public float $currentStock,
        public float $reorderLevel
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // Get user's preferences for this notification type
        $preferences = $notifiable->notificationPreferences()
            ->where('notification_type', self::class)
            ->first();

        if (!$preferences) {
            // Default channels if no preference set
            return ['database', 'broadcast'];
        }

        return $preferences->getEnabledChannels();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->warning()
            ->subject('Low Stock Alert: ' . $this->product->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The stock level for **' . $this->product->name . '** is running low.')
            ->line('Current Stock: **' . $this->currentStock . '** ' . $this->product->unit_of_measure)
            ->line('Reorder Level: **' . $this->reorderLevel . '** ' . $this->product->unit_of_measure)
            ->line('SKU: ' . $this->product->sku)
            ->action('View Product', url('/inventory/products/' . $this->product->id))
            ->line('Please consider replenishing the stock.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => $this->product->name . ' is running low on stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'warehouse_id' => $this->warehouseId,
            'current_stock' => $this->currentStock,
            'reorder_level' => $this->reorderLevel,
            'unit' => $this->product->unit_of_measure,
            'severity' => $this->getSeverity(),
            'action_url' => '/inventory/products/' . $this->product->id,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the notification severity based on how far below reorder level.
     */
    protected function getSeverity(): string
    {
        $percentageOfReorder = ($this->currentStock / $this->reorderLevel) * 100;

        if ($percentageOfReorder < 25) {
            return 'critical';
        } elseif ($percentageOfReorder < 50) {
            return 'high';
        } elseif ($percentageOfReorder < 75) {
            return 'medium';
        }

        return 'low';
    }
}
