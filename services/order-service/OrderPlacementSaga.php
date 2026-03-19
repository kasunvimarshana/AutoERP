<?php

namespace Services\Order\Saga;

use Shared\Saga\AbstractSaga;
use Shared\Saga\SagaStepInterface;
use Illuminate\Support\Facades\Http;

class OrderPlacementSaga extends AbstractSaga
{
    public function defineSteps(): void
    {
        $this->steps = [
            'reserve_inventory' => new ReserveInventoryStep(),
            'process_payment' => new ProcessPaymentStep(),
            'finalize_order' => new FinalizeOrderStep(),
        ];
    }
}

class ReserveInventoryStep implements SagaStepInterface
{
    public function execute(array $payload): void
    {
        // Call Inventory Service gRPC/REST
        // Http::post('http://inventory-service/api/v1/reserve', $payload);
    }

    public function compensate(array $payload): void
    {
        // Http::post('http://inventory-service/api/v1/release', $payload);
    }
}

class ProcessPaymentStep implements SagaStepInterface
{
    public function execute(array $payload): void
    {
        // Call Finance Service
    }

    public function compensate(array $payload): void
    {
        // Refund logic
    }
}

class FinalizeOrderStep implements SagaStepInterface
{
    public function execute(array $payload): void
    {
        // Update Order status to PAID
    }

    public function compensate(array $payload): void
    {
        // Mark Order as FAILED
    }
}
