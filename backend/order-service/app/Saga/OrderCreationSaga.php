<?php

namespace App\Saga;

use App\Models\Order;
use App\Models\SagaLog;
use App\Saga\Contracts\SagaStepInterface;
use Illuminate\Support\Facades\Log;

class OrderCreationSaga
{
    /** @var SagaStepInterface[] */
    private array $steps = [];
    private array $completedSteps = [];
    private array $context = [];

    public function addStep(SagaStepInterface $step): self
    {
        $this->steps[] = $step;
        return $this;
    }

    public function execute(Order $order): Order
    {
        $this->completedSteps = [];
        $this->context = [];

        foreach ($this->steps as $step) {
            try {
                Log::info("Saga: Executing step [{$step->getName()}]", ['order_id' => $order->id]);
                $stepContext = $step->execute($order, $this->context);
                $this->context = array_merge($this->context, $stepContext ?? []);
                $this->logStep($order, $step->getName(), 'completed', $stepContext ?? []);
                $this->completedSteps[] = ['step' => $step, 'context' => $stepContext ?? []];
                $order = $order->fresh(['items', 'sagaLogs']);
            } catch (\Throwable $e) {
                Log::error("Saga: Step [{$step->getName()}] FAILED", ['order_id' => $order->id, 'error' => $e->getMessage()]);
                $this->logStep($order, $step->getName(), 'failed', [], $e->getMessage());
                $this->compensate($order);
                throw new \RuntimeException("Order saga failed at step [{$step->getName()}]: {$e->getMessage()}", 0, $e);
            }
        }

        return $order->fresh(['items', 'sagaLogs']);
    }

    private function compensate(Order $order): void
    {
        foreach (array_reverse($this->completedSteps) as $completedStep) {
            $step = $completedStep['step'];
            try {
                Log::info("Saga: Compensating step [{$step->getName()}]", ['order_id' => $order->id]);
                $step->compensate($order, array_merge($this->context, $completedStep['context']));
                $this->logStepCompensated($order, $step->getName());
            } catch (\Throwable $e) {
                Log::critical("Saga: Compensation for [{$step->getName()}] FAILED", ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }
    }

    private function logStep(Order $order, string $stepName, string $status, array $payload = [], ?string $errorMessage = null): void
    {
        SagaLog::create([
            'order_id' => $order->id,
            'step_name' => $stepName,
            'status' => $status,
            'payload' => $payload,
            'error_message' => $errorMessage,
            'executed_at' => now(),
        ]);
    }

    private function logStepCompensated(Order $order, string $stepName): void
    {
        SagaLog::where('order_id', $order->id)
            ->where('step_name', $stepName)
            ->where('status', 'completed')
            ->latest()
            ->first()
            ?->update(['status' => 'compensated', 'compensated_at' => now()]);
    }
}
