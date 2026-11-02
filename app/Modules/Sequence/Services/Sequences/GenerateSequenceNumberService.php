<?php

declare(strict_types=1);

namespace Modules\Sequence\Services\Sequences;

use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Sequence\Constants\SequenceErrorCode;
use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Services\Contracts\SequenceDomainServiceInterface;
use Throwable;

final class GenerateSequenceNumberService
{
    public function __construct(
        private readonly SequenceRepositoryInterface $sequences,
        private readonly SequenceDomainServiceInterface $domain,
        private readonly TransactionManagerInterface $transactions,
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

            $result = $this->transactions->runInTransaction(function () use (
                $tenantId,
                $organizationUnitId,
                $documentType,
                $periodType,
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
                    $sequence = $this->sequences->create([
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'document_type' => $documentType,
                        'prefix' => $this->domain->normalizeOptionalText(
                            isset($payload['prefix']) ? (string) $payload['prefix'] : null,
                        ) ?? (string) config('sequence.defaults.prefix', ''),
                        'suffix' => $this->domain->normalizeOptionalText(
                            isset($payload['suffix']) ? (string) $payload['suffix'] : null,
                        ) ?? (string) config('sequence.defaults.suffix', ''),
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

                    $sequence = $this->sequences->findByScopeForUpdate(
                        $tenantId,
                        $organizationUnitId,
                        $documentType,
                        $periodValue,
                    );
                }

                if (! $sequence instanceof DataRecord) {
                    throw new \RuntimeException('Unable to resolve sequence for generation.');
                }

                $prefix = (string) $sequence->get('prefix', '');
                $suffix = (string) $sequence->get('suffix', '');
                $padding = (int) $sequence->get('padding', config('sequence.defaults.padding', 5));
                $number = (int) $sequence->get('next_number', 1);
                $rowVersion = (int) $sequence->get('row_version', 1);

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

                $generated = $this->domain->formatSequenceNumber($prefix, $suffix, $padding, $number, $tokens);

                $updated = $this->sequences->updateNextNumberWithVersion(
                    (int) $sequence->id(),
                    $rowVersion,
                    $number + 1,
                );

                if (! $updated instanceof DataRecord) {
                    throw new \RuntimeException(SequenceErrorCode::CONCURRENCY_CONFLICT);
                }

                return [
                    'sequence_id' => (int) $updated->id(),
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'document_type' => $documentType,
                    'period_type' => (string) $updated->get('period_type', $periodType),
                    'period_value' => $periodValue,
                    'generated_number' => $generated,
                    'consumed_number' => $number,
                    'next_number' => (int) $updated->get('next_number', $number + 1),
                    'row_version' => (int) $updated->get('row_version', $rowVersion + 1),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            $errorCode = $exception->getMessage() === SequenceErrorCode::CONCURRENCY_CONFLICT
                ? SequenceErrorCode::CONCURRENCY_CONFLICT
                : SequenceErrorCode::INVALID_VALUE;

            return Result::failure(new Error($errorCode, $exception->getMessage()));
        }
    }
}
