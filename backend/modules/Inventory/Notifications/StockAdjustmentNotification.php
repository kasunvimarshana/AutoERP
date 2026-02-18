<?php

namespace Modules\Inventory\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockLedger;

class StockAdjustmentNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Product $product,
        public StockLedger $stockLedger,
        public string $adjustmentType
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $preferences = $notifiable->notificationPreferences()
            ->where('notification_type', self::class)
            ->first();

        if (!$preferences) {
            return ['database', 'broadcast'];
        }

        return $preferences->getEnabledChannels();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $direction = $this->stockLedger->quantity > 0 ? 'increased' : 'decreased';
        $absQuantity = abs($this->stockLedger->quantity);

        return (new MailMessage)
            ->subject('Stock Adjustment: ' . $this->product->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A stock adjustment has been made for **' . $this->product->name . '**.')
            ->line('Adjustment Type: **' . $this->adjustmentType . '**')
            ->line('Quantity ' . $direction . ': **' . $absQuantity . '** ' . $this->product->unit_of_measure)
            ->line('SKU: ' . $this->product->sku)
            ->line('Reference: ' . ($this->stockLedger->reference_number ?? 'N/A'))
            ->action('View Product', url('/inventory/products/' . $this->product->id))
            ->line('This is an automated notification.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'stock_adjustment',
            'title' => 'Stock Adjustment',
            'message' => $this->product->name . ' stock has been adjusted',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'warehouse_id' => $this->stockLedger->warehouse_id,
            'quantity' => $this->stockLedger->quantity,
            'adjustment_type' => $this->adjustmentType,
            'reference_number' => $this->stockLedger->reference_number,
            'unit' => $this->product->unit_of_measure,
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
}
