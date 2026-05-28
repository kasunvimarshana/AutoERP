<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\ValuationConfigServiceInterface;
use Modules\Inventory\Application\Repositories\ValuationConfigRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;
use Throwable;

final class ValuationConfigService implements ValuationConfigServiceInterface
{
    public function __construct(
        private readonly ValuationConfigRepositoryInterface $valuationConfigRepository,
    ) {
    }

    public function createConfig(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $payload['row_version'] ??= 1;

            return Result::success($this->valuationConfigRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateConfig(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->valuationConfigRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'ValuationConfig not found.'));
            }

            $validation = $this->validatePayload(array_merge($existing->toArray(), $payload));
            if ($validation->isFailure()) {
                return $validation;
            }

            return Result::success($this->valuationConfigRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        if ($tenantId === null || $tenantId <= 0) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id is required for valuation configurations.',
            ));
        }

        if (array_key_exists('valuation_method', $payload) && $payload['valuation_method'] !== null) {
            $method = strtolower((string) $payload['valuation_method']);
            if (! in_array($method, InventoryValuationMethod::all(), true)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'valuation_method must be one of: ' . implode(', ', InventoryValuationMethod::all()),
                ));
            }
        }

        return Result::success(true);
    }
}
