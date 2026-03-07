<?php

namespace App\Saga\Steps;

use App\Saga\Contracts\SagaStepInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReserveInventoryStep implements SagaStepInterface
{
    public function getName(): string
    {
        return 'reserve_inventory';
    }

    public function execute(array $context): array
    {
        $inventoryUrl  = config('services.inventory_service.url');
        $serviceToken  = $context['service_token'] ?? '';

        $items = $context['items'] ?? [];

        foreach ($items as $item) {
            $response = Http::withToken($serviceToken)
                ->post("{$inventoryUrl}/api/inventory/reserve", [
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'reference_id' => $context['order_id'] ?? null,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Inventory reservation failed for product [{$item['product_id']}]: " . $response->body()
                );
            }

            Log::info('ReserveInventoryStep: reserved inventory', [
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
            ]);
        }

        $context['inventory_reserved'] = true;

        return $context;
    }

    public function compensate(array $context): void
    {
        $inventoryUrl = config('services.inventory_service.url');
        $serviceToken = $context['service_token'] ?? '';
        $items        = $context['items'] ?? [];

        foreach ($items as $item) {
            try {
                Http::withToken($serviceToken)
                    ->post("{$inventoryUrl}/api/inventory/release", [
                        'product_id'   => $item['product_id'],
                        'quantity'     => $item['quantity'],
                        'reference_id' => $context['order_id'] ?? null,
                    ]);

                Log::info('ReserveInventoryStep: released inventory', [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ]);
            } catch (\Throwable $e) {
                Log::error('ReserveInventoryStep: failed to release inventory', [
                    'product_id' => $item['product_id'],
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
