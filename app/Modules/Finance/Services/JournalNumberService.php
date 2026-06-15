<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\DTOs\CreateJournalEntryData;
use Modules\Finance\Enums\JournalType;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;

final class JournalNumberService
{
    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function resolve(CreateJournalEntryData $data): string
    {
        if ($data->journalNumber !== null && trim($data->journalNumber) !== '') {
            return trim($data->journalNumber);
        }

        [$documentType, $prefix] = $this->sequenceFor($data->journalType);

        $result = $this->sequences->execute([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => $documentType,
            'period_type' => 'yearly',
            'at_date' => $data->journalDate,
            'prefix' => $prefix,
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        $payload = $result->valueOrFail();

        return (string) $payload['generated_number'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function sequenceFor(JournalType $type): array
    {
        return match ($type) {
            JournalType::Contra => ['finance_contra_voucher', 'CV-{PERIOD}-'],
            JournalType::Adjustment => ['finance_adjustment_voucher', 'AV-{PERIOD}-'],
            JournalType::Opening => ['finance_opening_balance_voucher', 'OBV-{PERIOD}-'],
            JournalType::Reversal => ['finance_reversal_voucher', 'REV-{PERIOD}-'],
            default => ['finance_journal_voucher', 'JV-{PERIOD}-'],
        };
    }
}
