<?php

namespace App\Saga;

use App\Models\SagaTransaction;
use App\Saga\Contracts\SagaOrchestratorInterface;
use App\Saga\Contracts\SagaStepInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SagaOrchestrator implements SagaOrchestratorInterface
{
    /** @var SagaStepInterface[] */
    private array $steps = [];

    private string $type;

    public function __construct(string $type = 'order_saga')
    {
        $this->type = $type;
    }

    public function addStep(SagaStepInterface $step): static
    {
        $this->steps[] = $step;

        return $this;
    }

    public function reset(): static
    {
        $this->steps = [];

        return $this;
    }

    public function execute(array $payload): array
    {
        $sagaTransaction = DB::transaction(function () use ($payload) {
            return SagaTransaction::create([
                'id'        => Str::uuid()->toString(),
                'tenant_id' => $payload['tenant_id'] ?? null,
                'type'      => $this->type,
                'status'    => SagaTransaction::STATUS_STARTED,
                'steps'     => [],
                'payload'   => $payload,
            ]);
        });

        $context        = $payload;
        $executedSteps  = [];

        try {
            foreach ($this->steps as $step) {
                $sagaTransaction->status = SagaTransaction::STATUS_IN_PROGRESS;
                $sagaTransaction->save();

                Log::info("SagaOrchestrator: executing step [{$step->getName()}]", [
                    'saga_id' => $sagaTransaction->id,
                ]);

                $context = $step->execute($context);
                $executedSteps[] = $step;

                $sagaTransaction->appendStep([
                    'step'       => $step->getName(),
                    'status'     => 'completed',
                    'completed_at' => now()->toISOString(),
                ]);
            }

            DB::transaction(function () use ($sagaTransaction) {
                $sagaTransaction->status = SagaTransaction::STATUS_COMPLETED;
                $sagaTransaction->save();
            });

            Log::info("SagaOrchestrator: saga completed", ['saga_id' => $sagaTransaction->id]);

            return [
                'success'  => true,
                'context'  => $context,
                'saga_id'  => $sagaTransaction->id,
            ];
        } catch (Throwable $e) {
            Log::error("SagaOrchestrator: step failed, beginning compensation", [
                'saga_id' => $sagaTransaction->id,
                'error'   => $e->getMessage(),
            ]);

            $sagaTransaction->status        = SagaTransaction::STATUS_COMPENSATING;
            $sagaTransaction->error_message = $e->getMessage();
            $sagaTransaction->save();

            foreach (array_reverse($executedSteps) as $step) {
                try {
                    $step->compensate($context);
                    $sagaTransaction->appendStep([
                        'step'           => $step->getName(),
                        'status'         => 'compensated',
                        'compensated_at' => now()->toISOString(),
                    ]);
                } catch (Throwable $compensationException) {
                    Log::error("SagaOrchestrator: compensation failed for step [{$step->getName()}]", [
                        'saga_id' => $sagaTransaction->id,
                        'error'   => $compensationException->getMessage(),
                    ]);
                }
            }

            DB::transaction(function () use ($sagaTransaction) {
                $sagaTransaction->status = SagaTransaction::STATUS_COMPENSATED;
                $sagaTransaction->save();
            });

            return [
                'success'  => false,
                'context'  => $context,
                'saga_id'  => $sagaTransaction->id,
                'error'    => $e->getMessage(),
            ];
        }
    }
}
