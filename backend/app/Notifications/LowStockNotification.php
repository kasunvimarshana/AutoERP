<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $productId,
        private readonly string $productName,
        private readonly string $productSku,
        private readonly float $currentQuantity,
        private readonly float $minimumQuantity,
        private readonly int $locationId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'current_quantity' => $this->currentQuantity,
            'minimum_quantity' => $this->minimumQuantity,
            'location_id' => $this->locationId,
            'title' => 'Low Stock Alert',
            'message' => "Product '{$this->productName}' (SKU: {$this->productSku}) is running low. Current: {$this->currentQuantity}, Minimum: {$this->minimumQuantity}",
            'action_url' => "/inventory/products/{$this->productId}",
            'severity' => 'warning',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
