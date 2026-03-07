<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Saga\Contracts\SagaStepInterface;
use App\Services\InventoryServiceClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmOrderStep implements SagaStepInterface
{
    public function __construct(private readonly InventoryServiceClient $inventoryClient) {}

    public function getName(): string { return 'confirm_order'; }

    public function execute(Order $order, array $context = []): array
    {
        foreach ($order->items as $item) {
            try {
                $this->inventoryClient->confirmInventory($item->product_id, $item->warehouse_id, $item->quantity, $order->tenant_id);
            } catch (\Exception $e) {
                Log::error('ConfirmOrderStep: Failed to confirm inventory', ['product_id' => $item->product_id, 'error' => $e->getMessage()]);
            }
        }

        DB::transaction(fn() => $order->update(['status' => Order::STATUS_COMPLETED, 'completed_at' => now()]));

        return ['confirmed_at' => now()->toIso8601String()];
    }

    public function compensate(Order $order, array $context = []): void
    {
        DB::transaction(fn() => $order->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]));
    }
}
