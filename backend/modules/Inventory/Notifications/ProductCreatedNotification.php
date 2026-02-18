<?php

namespace Modules\Inventory\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Inventory\Models\Product;

class ProductCreatedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Product $product
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
        return (new MailMessage)
            ->subject('New Product Created: ' . $this->product->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new product has been added to the inventory.')
            ->line('Product: **' . $this->product->name . '**')
            ->line('SKU: ' . $this->product->sku)
            ->line('Type: ' . $this->product->product_type->value)
            ->action('View Product', url('/inventory/products/' . $this->product->id))
            ->line('This is an automated notification.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'product_created',
            'title' => 'New Product Created',
            'message' => 'New product "' . $this->product->name . '" has been added',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'product_type' => $this->product->product_type->value,
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
