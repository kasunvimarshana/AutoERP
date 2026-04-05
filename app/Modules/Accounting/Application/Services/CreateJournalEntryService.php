<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Accounting\Application\DTOs\CreateJournalEntryData;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class CreateJournalEntryService implements CreateJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $repo) {}

    public function execute(CreateJournalEntryData $data): JournalEntry
    {
        $lines = array_map(fn($l) => new JournalEntryLine(
            null,
            null,
            (int) $l['account_id'],
            (float) ($l['debit'] ?? 0),
            (float) ($l['credit'] ?? 0),
            $l['description'] ?? null,
            $l['reference_line'] ?? null,
        ), $data->lines);

        // Validate balance before persisting
        $totalDebit  = array_sum(array_map(fn($l) => $l->getDebitAmount(), $lines));
        $totalCredit = array_sum(array_map(fn($l) => $l->getCreditAmount(), $lines));
        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw new \Modules\Accounting\Domain\Exceptions\JournalEntryImbalancedException($totalDebit, $totalCredit);
        }

        return $this->repo->create([
            'tenant_id'        => $data->tenant_id,
            'reference'        => $data->reference,
            'description'      => $data->description,
            'transaction_date' => $data->transaction_date,
            'status'           => 'draft',
        ], $lines);
    }
}
