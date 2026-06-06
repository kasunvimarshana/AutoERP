<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\DTOs\CreateJournalEntryData;
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

        $result = $this->sequences->execute([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => 'finance_journal_'.$data->journalType->value,
            'period_type' => 'yearly',
            'at_date' => $data->journalDate,
            'prefix' => 'JE-{PERIOD}-',
            'padding' => 6,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        $payload = $result->valueOrFail();

        return (string) $payload['generated_number'];
    }
}
