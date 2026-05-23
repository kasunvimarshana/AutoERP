<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\Services;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Sequence\Application\Actions\DeleteSequenceRecordAction;
use Modules\Sequence\Application\Actions\FindSequenceRecordAction;
use Modules\Sequence\Application\Actions\ListSequenceRecordsAction;
use Modules\Sequence\Application\Actions\PersistSequenceRecordAction;
use Modules\Sequence\Application\DTOs\SequenceData;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Domain\Services\SequenceDomainService;

class SequenceService
{
    public function __construct(
        private readonly SequenceRepositoryInterface $sequences,
        private readonly ListSequenceRecordsAction $listAction,
        private readonly FindSequenceRecordAction $findAction,
        private readonly PersistSequenceRecordAction $persistAction,
        private readonly DeleteSequenceRecordAction $deleteAction,
        private readonly SequenceDomainService $domain,
    ) {
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function listSequences(array $criteria = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listAction->execute($this->sequences, $criteria, $perPage);
    }

    public function findSequence(int|string $id): Model
    {
        return $this->findAction->execute($this->sequences, 'Sequence', $id);
    }

    public function createSequence(SequenceData $data): Model
    {
        return $this->persistAction->create($this->sequences, $this->normalizeAttributes($data));
    }

    public function updateSequence(int|string $id, SequenceData $data): Model
    {
        $existing = $this->findSequence($id);

        return $this->persistAction->update($this->sequences, $existing, $this->normalizeAttributes($data));
    }

    public function deleteSequence(int|string $id): bool
    {
        return $this->deleteAction->execute($this->sequences, $this->findSequence($id));
    }

    public function nextNumber(
        int|string $tenantId,
        int|string|null $organizationUnitId,
        string $documentType,
        ?string $periodType = null,
        ?DateTimeInterface $atDate = null,
    ): string {
        $normalizedDocumentType = $this->domain->normalizeDocumentType($documentType);

        $definition = $this->sequences->findDefinitionForScopeDocument(
            $tenantId,
            $organizationUnitId,
            $normalizedDocumentType,
        );

        $resolvedPeriodType = $periodType !== null
            ? $this->domain->normalizePeriodType($periodType)
            : $this->domain->normalizePeriodType((string) ($definition?->getAttribute('period_type') ?? 'yearly'));

        $resolvedPeriodValue = $this->domain->resolvePeriodValue($resolvedPeriodType, $atDate);

        /** @var string $number */
        $number = $this->sequences->transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $normalizedDocumentType,
            $definition,
            $resolvedPeriodType,
            $resolvedPeriodValue,
        ): string {
            $sequence = $this->sequences->lockForScopeDocumentAndPeriod(
                $tenantId,
                $organizationUnitId,
                $normalizedDocumentType,
                $resolvedPeriodValue,
            );

            if ($sequence === null) {
                $sequence = $this->sequences->create([
                    'tenant_id' => (int) $tenantId,
                    'organization_unit_id' => $organizationUnitId !== null ? (int) $organizationUnitId : null,
                    'document_type' => $normalizedDocumentType,
                    'prefix' => $this->domain->normalizeText($definition?->getAttribute('prefix')),
                    'suffix' => $this->domain->normalizeText($definition?->getAttribute('suffix')),
                    'padding' => (int) ($definition?->getAttribute('padding') ?? 5),
                    'next_number' => 1,
                    'period_type' => $resolvedPeriodType,
                    'period_value' => $resolvedPeriodValue,
                    'metadata' => $this->domain->normalizeMetadata($definition?->getAttribute('metadata')),
                ]);
            }

            $nextNumber = (int) $sequence->getAttribute('next_number');
            $rowVersion = (int) $sequence->getAttribute('row_version');

            $this->sequences->update($sequence, [
                'next_number' => $nextNumber + 1,
                'row_version' => $rowVersion + 1,
            ]);

            return $this->domain->formatNumber(
                (string) $sequence->getAttribute('prefix'),
                $nextNumber,
                (int) $sequence->getAttribute('padding'),
                (string) $sequence->getAttribute('suffix'),
            );
        });

        return $number;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAttributes(SequenceData $data): array
    {
        $periodType = $this->domain->normalizePeriodType($data->periodType);

        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => $this->domain->normalizeDocumentType($data->documentType),
            'prefix' => $this->domain->normalizeText($data->prefix),
            'suffix' => $this->domain->normalizeText($data->suffix),
            'padding' => max(1, $data->padding),
            'next_number' => max(1, $data->nextNumber),
            'period_type' => $periodType,
            'period_value' => $data->periodValue ?? $this->domain->resolvePeriodValue($periodType),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
