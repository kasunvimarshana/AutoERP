<?php

namespace Enterprise\Core\Messaging;

/**
 * SagaOrchestrator - Manages distributed transactions across microservices.
 * Supports forward steps and compensating actions (rollbacks).
 */
class SagaOrchestrator
{
    protected array $steps = [];
    protected array $completedSteps = [];
    protected string $sagaId;

    public function __construct(string $sagaId)
    {
        $this->sagaId = $sagaId;
    }

    /**
     * Add a step to the Saga.
     * @param callable $forward The action to execute.
     * @param callable $compensate The action to execute if a later step fails.
     */
    public function addStep(callable $forward, callable $compensate)
    {
        $this->steps[] = ['forward' => $forward, 'compensate' => $compensate];
        return $this;
    }

    /**
     * Execute the Saga steps sequentially.
     * If any step fails, triggers compensation for all previously completed steps.
     */
    public function execute()
    {
        try {
            foreach ($this->steps as $index => $step) {
                $result = $step['forward']();
                $this->completedSteps[] = $step;
            }
            return true;
        } catch (\Exception $e) {
            $this->compensate();
            throw $e;
        }
    }

    /**
     * Compensate (rollback) all completed steps in reverse order.
     */
    protected function compensate()
    {
        $toCompensate = array_reverse($this->completedSteps);
        foreach ($toCompensate as $step) {
            try {
                $step['compensate']();
            } catch (\Exception $e) {
                // Log failure of compensation for manual intervention (critical)
                \Log::critical("Saga Compensation Failed: {$this->sagaId}", ['error' => $e->getMessage()]);
            }
        }
    }
}
