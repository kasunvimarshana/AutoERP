<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\Services\PurchaseLedgerNoteServiceInterface;
use Modules\Purchase\Application\Repositories\PurchaseLedgerNoteRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class PurchaseLedgerNoteService implements PurchaseLedgerNoteServiceInterface
{
    private const SOURCE_TYPES = [
        'purchase_order',
        'grn_header',
        'purchase_invoice',
        'purchase_payment',
        'purchase_advance',
        'purchase_return',
        'purchase_refund',
    ];

    public function __construct(private readonly PurchaseLedgerNoteRepositoryInterface $notes) {}

    public function list(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1) {
                return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $criteria = ['tenant_id' => $tenantId, 'is_visible_to_api' => true];
            foreach (['source_type', 'source_id', 'note_type'] as $field) {
                if (isset($payload[$field]) && $payload[$field] !== '') {
                    $criteria[$field] = $field === 'source_id' ? (int) $payload[$field] : (string) $payload[$field];
                }
            }

            return Result::success($this->notes->list($criteria));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function create(array $payload): Result
    {
        try {
            $validation = $this->validatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            return Result::success($this->notes->create(array_merge([
                'row_version' => 1,
                'note_type' => 'manual',
                'is_visible_to_api' => true,
            ], $payload)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->notes->findById($id);
            if (! $existing instanceof DataRecord) {
                return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'Purchase ledger note not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $existing->get('tenant_id', 0));
            if ($tenantId !== (int) $existing->get('tenant_id', 0)) {
                return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, 'Cross-tenant note update is not allowed.'));
            }

            unset($payload['tenant_id'], $payload['source_type'], $payload['source_id']);

            return Result::success($this->notes->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function delete(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->notes->findById($id);
            if (! $existing instanceof DataRecord) {
                return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'Purchase ledger note not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1 || $tenantId !== (int) $existing->get('tenant_id', 0)) {
                return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, 'Cross-tenant note delete is not allowed.'));
            }

            $this->notes->delete($id);

            return Result::success(['deleted' => true]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function validatePayload(array $payload): Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        $body = trim((string) ($payload['body'] ?? ''));

        if ($tenantId < 1 || $sourceId < 1 || $body === '') {
            return Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'tenant_id, source_id and body are required.',
            ));
        }

        if (! in_array($sourceType, self::SOURCE_TYPES, true)) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, 'Unsupported ledger note source_type.'));
        }

        return Result::success(true);
    }
}
