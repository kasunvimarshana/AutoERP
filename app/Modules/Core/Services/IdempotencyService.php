<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Modules\Core\Models\IdempotencyRecord;

final class IdempotencyService
{
    public function scopeHash(int $tenantId, ?int $organizationUnitId, string $operation, string $referenceHash): string
    {
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
        $scopeHash = $this->scopeHash($tenantId, $organizationUnitId, $operation, $referenceHash);
        $existing = $this->lockedByScopeHash($scopeHash);
        if ($existing instanceof IdempotencyRecord) {
            return $this->assertPayloadHash($existing, $payloadHash);
        }

        try {
            return IdempotencyRecord::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'operation' => $operation,
                'reference_hash' => $referenceHash,
                'scope_hash' => $scopeHash,
                'payload_hash' => $payloadHash,
                'reference_value' => $referenceValue,
                'status' => 'in_progress',
                'created_by' => $createdBy,
            ]);
        } catch (QueryException) {
            $existing = $this->lockedByScopeHash($scopeHash);
            if (! $existing instanceof IdempotencyRecord) {
                throw new InvalidArgumentException('Idempotency record could not be acquired.');
            }

            return $this->assertPayloadHash($existing, $payloadHash);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $documentIds
     */
    public function complete(IdempotencyRecord $record, array $result, array $documentIds): IdempotencyRecord
    {
        $locked = IdempotencyRecord::query()->lockForUpdate()->findOrFail($record->getKey());
        $locked->status = 'completed';
        $locked->result = $result;
        $locked->document_ids = $documentIds;
        $locked->completed_at = now();
        $locked->save();

        return $locked;
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
        if ((string) $record->payload_hash !== $payloadHash) {
            throw new InvalidArgumentException('Idempotency key was already used for a different request payload.');
        }

        return $record;
    }
}
