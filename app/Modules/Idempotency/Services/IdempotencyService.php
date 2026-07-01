<?php

declare(strict_types=1);

namespace Modules\Idempotency\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Contracts\ClockInterface;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Models\IdempotencyRecord;

final class IdempotencyService
{
    private const HASH_PATTERN = '/^[0-9a-f]{64}$/D';

    public function __construct(private readonly ClockInterface $clock) {}

    public function scopeHash(int $tenantId, ?int $organizationUnitId, string $operation, string $referenceHash): string
    {
        $operation = $this->normalizeOperation($operation);
        $referenceHash = $this->normalizeHash($referenceHash, 'Reference hash');
        $this->assertScope($tenantId, $organizationUnitId);

        return hash('sha256', implode('|', [
            (string) $tenantId,
            (string) ($organizationUnitId ?? 'none'),
            $operation,
            $referenceHash,
        ]));
    }

    public function acquire(
        int $tenantId,
        ?int $organizationUnitId,
        string $operation,
        string $referenceHash,
        string $payloadHash,
        ?string $referenceValue = null,
        ?int $createdBy = null,
    ): IdempotencyRecord {
        $this->assertActiveTransaction();
        $this->assertScope($tenantId, $organizationUnitId);
        $operation = $this->normalizeOperation($operation);
        $referenceHash = $this->normalizeHash($referenceHash, 'Reference hash');
        $payloadHash = $this->normalizeHash($payloadHash, 'Payload hash');
        $referenceValue = $this->normalizeReferenceValue($referenceValue);

        if ($createdBy !== null && $createdBy < 1) {
            throw new InvalidArgumentException('Created-by user identifier must be positive.');
        }

        $scopeHash = $this->scopeHash($tenantId, $organizationUnitId, $operation, $referenceHash);
        $existing = $this->lockedByScopeHash($scopeHash);
        if ($existing instanceof IdempotencyRecord) {
            return $this->assertPayloadHash($existing, $payloadHash);
        }

        $now = $this->clock->now();
        $inserted = IdempotencyRecord::query()->insertOrIgnore([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'operation' => $operation,
            'reference_hash' => $referenceHash,
            'scope_hash' => $scopeHash,
            'payload_hash' => $payloadHash,
            'reference_value' => $referenceValue,
            'status' => IdempotencyStatus::InProgress->value,
            'created_by' => $createdBy,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $record = $this->lockedByScopeHash($scopeHash);
        if (! $record instanceof IdempotencyRecord) {
            throw new LogicException('Unable to persist idempotency record.');
        }

        $record->wasRecentlyCreated = $inserted === 1;

        return $this->assertPayloadHash($record, $payloadHash);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $documentIds
     */
    public function complete(IdempotencyRecord $record, array $result, array $documentIds): IdempotencyRecord
    {
        $this->assertActiveTransaction();

        $locked = IdempotencyRecord::query()->lockForUpdate()->findOrFail($record->getKey());
        if ($locked->status !== IdempotencyStatus::InProgress) {
            throw new LogicException('Only an in-progress idempotency record can be completed.');
        }

        $locked->forceFill([
            'status' => IdempotencyStatus::Completed->value,
            'result' => $result,
            'document_ids' => $documentIds,
            'completed_at' => $this->clock->now(),
        ])->save();

        return $locked->refresh();
    }

    private function lockedByScopeHash(string $scopeHash): ?IdempotencyRecord
    {
        return IdempotencyRecord::query()
            ->where('scope_hash', $scopeHash)
            ->lockForUpdate()
            ->first();
    }

    private function assertPayloadHash(IdempotencyRecord $record, string $payloadHash): IdempotencyRecord
    {
        if (! hash_equals((string) $record->payload_hash, $payloadHash)) {
            throw new InvalidArgumentException('Idempotency key was already used for a different request payload.');
        }

        return $record;
    }

    private function assertActiveTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Idempotency acquisition and completion must run inside a database transaction.');
        }
    }

    private function assertScope(int $tenantId, ?int $organizationUnitId): void
    {
        if ($tenantId < 1) {
            throw new InvalidArgumentException('Tenant identifier must be positive.');
        }

        if ($organizationUnitId !== null && $organizationUnitId < 1) {
            throw new InvalidArgumentException('Organization-unit identifier must be positive when provided.');
        }
    }

    private function normalizeOperation(string $operation): string
    {
        $operation = trim($operation);
        if ($operation === '' || mb_strlen($operation) > 120) {
            throw new InvalidArgumentException('Idempotency operation must contain between 1 and 120 characters.');
        }

        return $operation;
    }

    private function normalizeHash(string $hash, string $label): string
    {
        $hash = strtolower(trim($hash));
        if (preg_match(self::HASH_PATTERN, $hash) !== 1) {
            throw new InvalidArgumentException($label.' must be a SHA-256 hexadecimal hash.');
        }

        return $hash;
    }

    private function normalizeReferenceValue(?string $referenceValue): ?string
    {
        if ($referenceValue === null) {
            return null;
        }

        $referenceValue = trim($referenceValue);
        if ($referenceValue === '') {
            return null;
        }

        if (mb_strlen($referenceValue) > 255) {
            throw new InvalidArgumentException('Idempotency reference value cannot exceed 255 characters.');
        }

        return $referenceValue;
    }
}
