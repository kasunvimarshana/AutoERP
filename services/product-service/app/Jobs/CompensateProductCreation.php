<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Saga compensating transaction: undo product creation when inventory sync fails
 * after all retries are exhausted.
 */
class CompensateProductCreation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 30;

    public function __construct(
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $tenantId,
        public readonly string $triggeredBy,
        public readonly string $reason,
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $product = Product::withoutGlobalScope('tenant')
                ->where('id', $this->productId)
                ->where('tenant_id', $this->tenantId)
                ->first();

            if (! $product) {
                Log::warning('CompensateProductCreation: product already removed', [
                    'product_id' => $this->productId,
                ]);
                return;
            }

            $product->forceDelete();

            Log::warning('CompensateProductCreation: product rolled back due to saga failure', [
                'product_id'   => $this->productId,
                'sku'          => $this->sku,
                'tenant_id'    => $this->tenantId,
                'triggered_by' => $this->triggeredBy,
                'reason'       => $this->reason,
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('CompensateProductCreation permanently failed — MANUAL INTERVENTION REQUIRED', [
            'product_id' => $this->productId,
            'sku'        => $this->sku,
            'tenant_id'  => $this->tenantId,
            'error'      => $exception->getMessage(),
        ]);
    }
}
