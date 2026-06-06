<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\UseCases\Sequences;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Domain\Constants\SequenceErrorCode;
use Modules\Sequence\Domain\Contracts\SequenceDomainServiceInterface;
use Throwable;

final class CreateSequenceService
{
    public function __construct(
        private readonly SequenceRepositoryInterface $sequences,
        private readonly SequenceDomainServiceInterface $domain,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
            if ($tenantId < 1) {
                return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, 'Tenant id is required.'));
            }

            $organizationUnitId = array_key_exists('organization_unit_id', $payload)
                ? (isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null)
                : null;

            $documentType = $this->domain->normalizeDocumentType((string) ($payload['document_type'] ?? ''));
            $periodType = $this->domain->normalizePeriodType(
                isset($payload['period_type']) ? (string) $payload['period_type'] : null,
            );
            $periodValue = $this->domain->normalizePeriodValue(
                isset($payload['period_value']) ? (string) $payload['period_value'] : null,
            );

            if ($this->sequences->findByScope($tenantId, $organizationUnitId, $documentType, $periodValue) !== null) {
                return Result::failure(
                    new Error(SequenceErrorCode::CONFLICT, 'Sequence already exists for the provided scope.')
                );
            }

            $record = $this->sequences->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'document_type' => $documentType,
                'prefix' => $this->domain->normalizeOptionalText(
                    isset($payload['prefix']) ? (string) $payload['prefix'] : null,
                ) ?? '',
                'suffix' => $this->domain->normalizeOptionalText(
                    isset($payload['suffix']) ? (string) $payload['suffix'] : null,
                ) ?? '',
                'padding' => $this->domain->normalizePadding(
                    $payload['padding'] ?? config('sequence.defaults.padding', 5),
                ),
                'next_number' => $this->domain->normalizeNextNumber(
                    $payload['next_number'] ?? config('sequence.defaults.next_number', 1),
                ),
                'period_type' => $periodType,
                'period_value' => $periodValue,
                'scope_key' => $this->domain->scopeKey($organizationUnitId, $periodValue),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'row_version' => 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
