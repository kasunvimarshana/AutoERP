<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\SerialServiceInterface;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class SerialService implements SerialServiceInterface
{
    public function __construct(private readonly SerialRepositoryInterface $serialRepository)
    {
    }

    public function createSerial(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $payload['row_version'] ??= 1;
            $payload['status'] ??= 'AVAILABLE';

            return Result::success($this->serialRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateSerial(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->serialRepository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(InventoryErrorCode::NOT_FOUND, 'Serial not found.'));
            }

            $validation = $this->validatePayload(array_merge($existing->toArray(), $payload), false);
            if ($validation->isFailure()) {
                return $validation;
            }

            if ($this->isLocked($existing) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'Locked serials are immutable.',
                ));
            }

            return Result::success($this->serialRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload, bool $creating = true): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
        $serialNumber = trim((string) ($payload['serial_number'] ?? ''));

        if ($tenantId === null || $itemId === null || $serialNumber === '') {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_VALUE,
                'tenant_id, item_id and serial_number are required.',
            ));
        }

        if (array_key_exists('status', $payload) && $payload['status'] !== null) {
            $status = strtoupper((string) $payload['status']);
            if (! in_array($status, ['AVAILABLE', 'SOLD', 'RETURNED', 'DAMAGED', 'SCRAPPED'], true)) {
                return Result::failure(new Error(
                    InventoryErrorCode::INVALID_VALUE,
                    'status must be AVAILABLE, SOLD, RETURNED, DAMAGED or SCRAPPED.',
                ));
            }
        }

        return Result::success(true);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (
            [
                'tenant_id',
                'organization_unit_id',
                'item_id',
                'variant_id',
                'serial_number',
                'batch_id',
                'current_location_id',
                'current_owner_type',
                'current_owner_id',
                'warranty_expiry',
                'manufacture_date',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isLocked(DataRecord $serial): bool
    {
        return in_array(strtoupper((string) $serial->get('status')), ['SOLD', 'DAMAGED', 'SCRAPPED'], true);
    }
}
