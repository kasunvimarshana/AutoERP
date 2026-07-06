<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Modules\Customer\Models\Customer;
use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Services\Contracts\SequenceDomainServiceInterface;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class CustomerNumberService
{
    private const DOCUMENT_TYPE = 'customer';
    private const PERIOD_TYPE = 'infinite';
    private const PREFIX = 'CUS-';
    private const PADDING = 6;
    private const MAX_GENERATION_ATTEMPTS = 3;

    public function __construct(
        private readonly GenerateSequenceNumberService $sequences,
        private readonly SequenceRepositoryInterface $sequenceRepository,
        private readonly SequenceDomainServiceInterface $sequenceDomain,
    ) {}

    public function next(int $tenantId): string
    {
        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $candidate = $this->generate($tenantId);
            if (! $this->exists($tenantId, $candidate)) {
                return $candidate;
            }

            $this->advanceSequencePastExistingNumbers($tenantId);
        }

        throw new RuntimeException('Unable to generate a unique customer number.');
    }

    private function generate(int $tenantId): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'document_type' => self::DOCUMENT_TYPE,
            'period_type' => self::PERIOD_TYPE,
            'prefix' => self::PREFIX,
            'padding' => self::PADDING,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }

    private function exists(int $tenantId, string $customerNumber): bool
    {
        return Customer::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('customer_number', $customerNumber)
            ->exists();
    }

    private function advanceSequencePastExistingNumbers(int $tenantId): void
    {
        $sequence = $this->sequenceRepository->findByScopeForUpdate(
            $tenantId,
            null,
            self::DOCUMENT_TYPE,
            null,
        );

        if ($sequence === null) {
            return;
        }

        $currentNextNumber = (int) $sequence->get('next_number', 1);
        $highestExistingNumber = $this->highestExistingNumber($tenantId);
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
            throw new RuntimeException('Customer sequence was changed while resynchronizing. Try again.');
        }
    }

    private function highestExistingNumber(int $tenantId): int
    {
        $prefix = self::PREFIX;
        $customerNumbers = Customer::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('customer_number', 'like', $prefix.'%')
            ->pluck('customer_number');

        $highest = 0;
        foreach ($customerNumbers as $customerNumber) {
            $suffix = substr((string) $customerNumber, strlen($prefix));
            if ($suffix === '' || ! ctype_digit($suffix)) {
                continue;
            }

            $highest = max($highest, (int) $suffix);
        }

        return $highest;
    }
}
