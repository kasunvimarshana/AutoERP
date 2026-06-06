<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\UseCases\Sequences;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Domain\Constants\SequenceErrorCode;
use Modules\Sequence\Domain\Contracts\SequenceDomainServiceInterface;
use Throwable;

final class RollbackSequenceNumberService
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
            $periodValue = $this->domain->resolvePeriodValue(
                $periodType,
                isset($payload['period_value']) ? (string) $payload['period_value'] : null,
                isset($payload['at_date']) ? (string) $payload['at_date'] : null,
            );

            $result = $this->sequences->transaction(function () use (
                $tenantId,
                $organizationUnitId,
                $documentType,
                $periodValue,
                $payload,
            ): array {
                $sequence = $this->sequences->findByScopeForUpdate(
                    $tenantId,
                    $organizationUnitId,
                    $documentType,
                    $periodValue,
                );

                if (! $sequence instanceof DataRecord) {
                    throw new \RuntimeException(SequenceErrorCode::NOT_FOUND);
                }

                $currentNext = (int) $sequence->get('next_number', 1);
                $rowVersion = (int) $sequence->get('row_version', 1);

                if ($currentNext <= 1) {
                    throw new \RuntimeException('Sequence cannot be rolled back below 1.');
                }

                if (
                    isset($payload['expected_next_number'])
                    && (int) $payload['expected_next_number'] !== $currentNext
                ) {
                    throw new \RuntimeException(SequenceErrorCode::CONCURRENCY_CONFLICT);
                }

                $updated = $this->sequences->updateNextNumberWithVersion(
                    (int) $sequence->id(),
                    $rowVersion,
                    $currentNext - 1,
                );

                if (! $updated instanceof DataRecord) {
                    throw new \RuntimeException(SequenceErrorCode::CONCURRENCY_CONFLICT);
                }

                return [
                    'sequence_id' => (int) $updated->id(),
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'document_type' => $documentType,
                    'period_value' => $periodValue,
                    'next_number' => (int) $updated->get('next_number', $currentNext - 1),
                    'row_version' => (int) $updated->get('row_version', $rowVersion + 1),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $errorCode = match ($message) {
                SequenceErrorCode::NOT_FOUND => SequenceErrorCode::NOT_FOUND,
                SequenceErrorCode::CONCURRENCY_CONFLICT => SequenceErrorCode::CONCURRENCY_CONFLICT,
                default => SequenceErrorCode::INVALID_VALUE,
            };

            return Result::failure(new Error($errorCode, $message));
        }
    }
}
