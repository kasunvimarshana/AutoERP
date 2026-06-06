<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\UseCases\Sequences;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Domain\Constants\SequenceErrorCode;
use Modules\Sequence\Domain\Contracts\SequenceDomainServiceInterface;
use Throwable;

final class UpdateSequenceService
{
    public function __construct(
        private readonly SequenceRepositoryInterface $sequences,
        private readonly SequenceDomainServiceInterface $domain,
    ) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->sequences->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(SequenceErrorCode::NOT_FOUND, 'Sequence not found.'));
            }

            $tenantId = array_key_exists('tenant_id', $payload)
                ? (int) $payload['tenant_id']
                : (int) $existing->require('tenant_id');

            if ($tenantId < 1) {
                return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, 'Tenant id is required.'));
            }

            $existingOrganizationUnitId = $existing->get('organization_unit_id');
            $existingPeriodValue = $existing->get('period_value');
            $existingPeriodType = $existing->get('period_type', config('sequence.defaults.period_type', 'yearly'));

            $organizationUnitId = array_key_exists('organization_unit_id', $payload)
                ? (isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null)
                : (isset($existingOrganizationUnitId) ? (int) $existingOrganizationUnitId : null);

            $documentType = array_key_exists('document_type', $payload)
                ? $this->domain->normalizeDocumentType((string) $payload['document_type'])
                : $this->domain->normalizeDocumentType((string) $existing->require('document_type'));

            $periodValue = array_key_exists('period_value', $payload)
                ? $this->domain->normalizePeriodValue(
                    isset($payload['period_value']) ? (string) $payload['period_value'] : null,
                )
                : $this->domain->normalizePeriodValue(
                    isset($existingPeriodValue) ? (string) $existingPeriodValue : null,
                );

            $conflict = $this->sequences->findByScope($tenantId, $organizationUnitId, $documentType, $periodValue);
            if ($conflict !== null && (string) $conflict->id() !== (string) $existing->id()) {
                return Result::failure(
                    new Error(SequenceErrorCode::CONFLICT, 'Sequence already exists for the provided scope.')
                );
            }

            $record = $this->sequences->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'document_type' => $documentType,
                'prefix' => array_key_exists('prefix', $payload)
                    ? ($this->domain->normalizeOptionalText(
                        isset($payload['prefix']) ? (string) $payload['prefix'] : null,
                    ) ?? '')
                    : (string) $existing->get('prefix', ''),
                'suffix' => array_key_exists('suffix', $payload)
                    ? ($this->domain->normalizeOptionalText(
                        isset($payload['suffix']) ? (string) $payload['suffix'] : null,
                    ) ?? '')
                    : (string) $existing->get('suffix', ''),
                'padding' => array_key_exists('padding', $payload)
                    ? $this->domain->normalizePadding($payload['padding'])
                    : (int) $existing->get('padding', config('sequence.defaults.padding', 5)),
                'next_number' => array_key_exists('next_number', $payload)
                    ? $this->domain->normalizeNextNumber($payload['next_number'])
                    : (int) $existing->get('next_number', config('sequence.defaults.next_number', 1)),
                'period_type' => array_key_exists('period_type', $payload)
                    ? $this->domain->normalizePeriodType(
                        isset($payload['period_type']) ? (string) $payload['period_type'] : null,
                    )
                    : $this->domain->normalizePeriodType((string) $existingPeriodType),
                'period_value' => $periodValue,
                'scope_key' => $this->domain->scopeKey($organizationUnitId, $periodValue),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $this->domain->normalizeMetadata($existing->get('metadata', [])),
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
