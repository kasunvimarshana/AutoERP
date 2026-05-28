<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\WriteOffServiceInterface;
use Modules\Payment\Application\Repositories\WriteOffRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class WriteOffService implements WriteOffServiceInterface
{
    public function __construct(private readonly WriteOffRepositoryInterface $writeOffRepository)
    {
    }

    public function createWriteOff(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
            $documentType = trim((string) ($payload['document_type'] ?? ''));
            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;
            $amount = round((float) ($payload['amount'] ?? 0), 4);

            if ($tenantId < 1 || $documentType === '' || $documentId < 1 || $amount <= 0) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'tenant_id, document_type, document_id and amount are required.',
                ));
            }

            $payload['row_version'] = (int) ($payload['row_version'] ?? 1);
            $payload['status'] = $payload['status'] ?? 'draft';

            return Result::success($this->writeOffRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateWriteOff(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->writeOffRepository->findById($id);
            if (! $existing instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Write-off not found.'));
            }

            $status = strtolower((string) $existing->get('status', 'draft'));
            if (
                in_array($status, ['posted', 'reversed', 'cancelled'], true)
                && $this->containsStructuralMutation($payload)
            ) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Posted/reversed/cancelled write-offs are immutable for structural fields.',
                ));
            }

            if (array_key_exists('amount', $payload) && (float) $payload['amount'] <= 0) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'amount must be greater than zero.',
                ));
            }

            return Result::success($this->writeOffRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /** @param array<string, mixed> $payload */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (
            [
                'tenant_id',
                'organization_unit_id',
                'document_type',
                'document_id',
                'amount',
                'journal_entry_id',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
