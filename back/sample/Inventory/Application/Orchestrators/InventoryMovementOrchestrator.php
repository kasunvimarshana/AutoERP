<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Orchestrators;

use Throwable;
use Modules\Inventory\Application\DTOs\PostInventoryMovementDTO;
use Modules\Inventory\Application\UseCases\PostInventoryMovementUseCase;
use Modules\Inventory\Domain\Services\InventoryConfigService;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovement;

class InventoryMovementOrchestrator
{
    public function __construct(
        private readonly PostInventoryMovementUseCase $useCase,
        private readonly InventoryConfigService $configService,
    ) {
    }

    public function post(PostInventoryMovementDTO $dto): StockMovement
    {
        $attempts = max(1, $this->configService->retryAttempts());

        for ($current = 1; $current <= $attempts; $current++) {
            try {
                return $this->useCase->execute($dto);
            } catch (Throwable $e) {
                if ($current >= $attempts || !$this->isRetryable($e)) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Inventory movement orchestration exhausted retries unexpectedly.');
    }

    private function isRetryable(Throwable $throwable): bool
    {
        $message = strtolower($throwable->getMessage());

        return str_contains($message, 'deadlock')
            || str_contains($message, 'lock wait timeout')
            || str_contains($message, 'serialization failure');
    }
}
