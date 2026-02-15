<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class StockExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $productId,
        private readonly string $productName,
        private readonly string $productSku,
        private readonly string $batchNumber,
        private readonly float $quantity,
        private readonly Carbon $expiryDate,
        private readonly int $daysUntilExpiry,
        private readonly int $locationId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $severity = match (true) {
            $this->daysUntilExpiry <= 7 => 'critical',
            $this->daysUntilExpiry <= 30 => 'warning',
            default => 'info',
        };

        return [
            'type' => 'stock_expiry',
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'batch_number' => $this->batchNumber,
            'quantity' => $this->quantity,
            'expiry_date' => $this->expiryDate->toDateString(),
            'days_until_expiry' => $this->daysUntilExpiry,
            'location_id' => $this->locationId,
            'title' => 'Stock Expiry Alert',
            'message' => "Batch {$this->batchNumber} of '{$this->productName}' (SKU: {$this->productSku}) expires in {$this->daysUntilExpiry} days. Quantity: {$this->quantity}",
            'action_url' => "/inventory/products/{$this->productId}?batch={$this->batchNumber}",
            'severity' => $severity,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
