<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\UseCases\Sequences;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\PreviewSequenceNumberServiceInterface;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Domain\Constants\SequenceErrorCode;
use Modules\Sequence\Domain\Contracts\SequenceDomainServiceInterface;
use Throwable;

final class PreviewSequenceNumberService implements PreviewSequenceNumberServiceInterface
{
    public function __construct(
        private readonly SequenceRepositoryInterface $sequences,
        private readonly SequenceDomainServiceInterface $domain,
    ) {
    }

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
            $periodValue = $this->domain->resolvePeriodValue(
                $periodType,
                isset($payload['period_value']) ? (string) $payload['period_value'] : null,
                isset($payload['at_date']) ? (string) $payload['at_date'] : null,
            );

            $sequence = $this->sequences->findByScope($tenantId, $organizationUnitId, $documentType, $periodValue);

            $prefix = $this->domain->normalizeOptionalText(
                isset($payload['prefix']) ? (string) $payload['prefix'] : null,
            ) ?? '';
            $suffix = $this->domain->normalizeOptionalText(
                isset($payload['suffix']) ? (string) $payload['suffix'] : null,
            ) ?? '';
            $padding = $this->domain->normalizePadding($payload['padding'] ?? config('sequence.defaults.padding', 5));
            $nextNumber = $this->domain->normalizeNextNumber(
                $payload['next_number'] ?? config('sequence.defaults.next_number', 1),
            );

            if ($sequence !== null) {
                $prefix = (string) $sequence->get('prefix', $prefix);
                $suffix = (string) $sequence->get('suffix', $suffix);
                $padding = (int) $sequence->get('padding', $padding);
                $nextNumber = (int) $sequence->get('next_number', $nextNumber);
                $periodType = (string) $sequence->get('period_type', $periodType);
            }

            $tokens = [
                'tenant_id' => $tenantId,
                'org_id' => $organizationUnitId,
                'doc_type' => $documentType,
                'period' => $periodValue,
            ];
            if (isset($payload['tokens']) && is_array($payload['tokens'])) {
                foreach ($payload['tokens'] as $key => $value) {
                    if (is_string($key)) {
                        $tokens[$key] = is_scalar($value) || $value === null ? $value : null;
                    }
                }
            }

            $documentNumber = $this->domain->formatSequenceNumber(
                $prefix,
                $suffix,
                $padding,
                $nextNumber,
                $tokens,
            );

            return Result::success([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'document_type' => $documentType,
                'period_type' => $periodType,
                'period_value' => $periodValue,
                'next_number' => $nextNumber,
                'preview_number' => $documentNumber,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SequenceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
