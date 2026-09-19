<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\Vehicle\Models\Vehicle;
use RuntimeException;

final class VehicleNumberService
{
    private const NUMBER_DOCUMENT_TYPE = 'vehicle';

    private const CODE_DOCUMENT_TYPE = 'vehicle_code';

    private const PERIOD_TYPE = 'infinite';

    private const PREFIX = 'VEH-';

    private const PADDING = 6;

    private const MAX_GENERATION_ATTEMPTS = 3;

    public function __construct(
        private readonly GenerateSequenceNumberService $sequences,
        private readonly SequenceRepositoryInterface $sequenceRepository,
    ) {}

    public function next(int $tenantId): string
    {
        return $this->nextUnique($tenantId, self::NUMBER_DOCUMENT_TYPE, 'vehicle_number');
    }

    public function nextCode(int $tenantId): string
    {
        return $this->nextUnique($tenantId, self::CODE_DOCUMENT_TYPE, 'code');
    }

    private function nextUnique(int $tenantId, string $documentType, string $column): string
    {
        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $candidate = $this->generate($tenantId, $documentType);
            if (! $this->exists($tenantId, $column, $candidate)) {
                return $candidate;
            }

            $this->advanceSequencePastExistingReferences($tenantId, $documentType, $column);
        }

        throw new RuntimeException('Unable to generate a unique vehicle reference.');
    }

    private function generate(int $tenantId, string $documentType): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'document_type' => $documentType,
            'period_type' => self::PERIOD_TYPE,
            'prefix' => self::PREFIX,
            'padding' => self::PADDING,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }

    private function exists(int $tenantId, string $column, string $candidate): bool
    {
        return Vehicle::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where($column, $candidate)
            ->exists();
    }

    private function advanceSequencePastExistingReferences(
        int $tenantId,
        string $documentType,
        string $column,
    ): void {
        $sequence = $this->sequenceRepository->findByScopeForUpdate(
            $tenantId,
            null,
            $documentType,
            null,
        );

        if ($sequence === null) {
            return;
        }

        $currentNextNumber = (int) $sequence->get('next_number', 1);
        $highestExistingNumber = $this->highestExistingNumber($tenantId, $column);
        $nextAvailableNumber = max($currentNextNumber, $highestExistingNumber + 1);

        if ($nextAvailableNumber === $currentNextNumber) {
            return;
        }

        $updated = $this->sequenceRepository->updateNextNumberWithVersion(
            (int) $sequence->id(),
            (int) $sequence->get('row_version', 1),
            $nextAvailableNumber,
        );

        if ($updated === null) {
            throw new RuntimeException('Vehicle sequence was changed while resynchronizing. Try again.');
        }
    }

    private function highestExistingNumber(int $tenantId, string $column): int
    {
        $references = Vehicle::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where($column, 'like', self::PREFIX.'%')
            ->pluck($column);

        $highest = 0;
        foreach ($references as $reference) {
            $suffix = substr((string) $reference, strlen(self::PREFIX));
            if ($suffix === '' || ! ctype_digit($suffix)) {
                continue;
            }

            $highest = max($highest, (int) $suffix);
        }

        return $highest;
    }
}
