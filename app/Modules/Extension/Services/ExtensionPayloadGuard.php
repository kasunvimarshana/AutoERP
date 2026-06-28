<?php

declare(strict_types=1);

namespace Modules\Extension\Services;

use InvalidArgumentException;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;

final class ExtensionPayloadGuard
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly ExtensionEntityReferenceValidator $references,
    ) {}

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function forCreate(
        array $payload,
        string $typeField,
        string $idField,
    ): array {
        $tenantId = $this->currentTenant->requireCurrent()->tenantId();
        $payload['tenant_id'] = $tenantId;
        $payload['row_version'] = 1;

        return $this->validateReferences($payload, $typeField, $idField);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function forUpdate(
        DataRecord $existing,
        array $payload,
        string $typeField,
        string $idField,
    ): array {
        $tenantId = $this->currentTenant->requireCurrent()->tenantId();
        if ((int) $existing->require('tenant_id') !== $tenantId) {
            throw new InvalidArgumentException('Extension record does not belong to the active tenant.');
        }

        unset($payload['tenant_id']);
        $merged = array_replace($existing->toArray(), $payload);
        $validated = $this->validateReferences($merged, $typeField, $idField);

        $payload[$typeField] = $validated[$typeField];
        $payload[$idField] = $validated[$idField];
        if (array_key_exists('source_type', $validated)) {
            $payload['source_type'] = $validated['source_type'];
            $payload['source_id'] = $validated['source_id'];
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function validateReferences(array $payload, string $typeField, string $idField): array
    {
        $type = is_scalar($payload[$typeField] ?? null) ? (string) $payload[$typeField] : '';
        $id = is_numeric($payload[$idField] ?? null) ? (int) $payload[$idField] : 0;
        $payload[$typeField] = $this->references->normalizeAndAssertExists($type, $id);
        $payload[$idField] = $id;

        $sourceType = is_scalar($payload['source_type'] ?? null)
            ? trim((string) $payload['source_type'])
            : '';
        $sourceId = is_numeric($payload['source_id'] ?? null) ? (int) $payload['source_id'] : null;

        if ($sourceType === '' && $sourceId === null) {
            unset($payload['source_type'], $payload['source_id']);

            return $payload;
        }

        if ($sourceType === '' || $sourceId === null || $sourceId < 1) {
            throw new InvalidArgumentException('Source type and source identifier must be provided together.');
        }

        $payload['source_type'] = $this->references->normalizeAndAssertExists($sourceType, $sourceId);
        $payload['source_id'] = $sourceId;

        return $payload;
    }
}
