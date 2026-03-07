<?php
namespace App\Services;
use App\Events\OrderConfirmed;
use App\Jobs\CompensateOrderSaga;
use App\Jobs\ConfirmOrderJob;
use App\Jobs\ProcessPaymentJob;
use App\Jobs\ReserveInventoryJob;
use App\Models\Order;
use App\Models\SagaLog;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Support\Str;

class OrderSagaService {
    public function __construct(private readonly OrderRepositoryInterface $orderRepo) {}

    public function startSaga(Order $order): SagaLog {
        $log = SagaLog::create(['order_id' => $order->id, 'saga_id' => (string)Str::uuid(), 'current_step' => SagaLog::STEP_RESERVE_INVENTORY, 'status' => 'started', 'steps_completed' => [], 'steps_failed' => [], 'compensation_required' => false, 'compensation_completed' => false]);
        ReserveInventoryJob::dispatch($order->id, $order->tenant_id);
        return $log;
    }

    public function onInventoryReserved(Order $order): void {
        $log = SagaLog::where('order_id', $order->id)->first();
        if ($log) {
            $completed = $log->steps_completed ?? [];
            $completed[] = SagaLog::STEP_RESERVE_INVENTORY;
            $log->update(['current_step' => SagaLog::STEP_PROCESS_PAYMENT, 'steps_completed' => $completed]);
        }
        ProcessPaymentJob::dispatch($order->id, $order->tenant_id);
    }

    public function onPaymentProcessed(Order $order): void {
        $log = SagaLog::where('order_id', $order->id)->first();
        if ($log) {
            $completed = $log->steps_completed ?? [];
            $completed[] = SagaLog::STEP_PROCESS_PAYMENT;
            $log->update(['current_step' => SagaLog::STEP_CONFIRM_ORDER, 'steps_completed' => $completed]);
        }
        ConfirmOrderJob::dispatch($order->id, $order->tenant_id);
    }

    public function onOrderConfirmed(Order $order): void {
        $log = SagaLog::where('order_id', $order->id)->first();
        if ($log) {
            $completed = $log->steps_completed ?? [];
            $completed[] = SagaLog::STEP_CONFIRM_ORDER;
            $log->update(['status' => 'completed', 'current_step' => null, 'steps_completed' => $completed]);
        }
        event(new OrderConfirmed($order));
    }

    public function compensate(Order $order, string $failedStep, string $reason): void {
        $log = SagaLog::where('order_id', $order->id)->first();
        if ($log) {
            $failed = $log->steps_failed ?? [];
            $failed[] = $failedStep;
            $log->update(['status' => 'failed', 'steps_failed' => $failed, 'compensation_required' => true, 'failure_reason' => $reason]);
        }
        CompensateOrderSaga::dispatch($order->id, $failedStep, $reason);
    }

    public function getSagaState(string $orderId): ?SagaLog {
        return SagaLog::where('order_id', $orderId)->first();
    }
}
