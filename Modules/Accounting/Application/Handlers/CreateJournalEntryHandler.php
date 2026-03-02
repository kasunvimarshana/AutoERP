<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Accounting\Application\Commands\CreateJournalEntryCommand;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;

class CreateJournalEntryHandler extends BaseHandler
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateJournalEntryCommand $command): JournalEntry
    {
        return $this->transaction(function () use ($command): JournalEntry {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateJournalEntryCommand $cmd): JournalEntry {
                    if (count($cmd->lines) < 2) {
                        throw new \DomainException('A journal entry must have at least 2 lines.');
                    }

                    $totalDebit = '0.0000';
                    $totalCredit = '0.0000';
                    $lines = [];

                    foreach ($cmd->lines as $lineData) {
                        $accountId = (int) $lineData['account_id'];
                        $account = $this->accountRepository->findById($accountId, $cmd->tenantId);

                        if ($account === null) {
                            throw new \DomainException("Account with ID {$accountId} not found for this tenant.");
                        }

                        $debit = bcadd((string) $lineData['debit_amount'], '0', 4);
                        $credit = bcadd((string) $lineData['credit_amount'], '0', 4);

                        $totalDebit = bcadd($totalDebit, $debit, 4);
                        $totalCredit = bcadd($totalCredit, $credit, 4);

                        $lines[] = new JournalEntryLine(
                            id: null,
                            journalEntryId: 0,
                            accountId: $accountId,
                            accountCode: $account->code,
                            accountName: $account->name,
                            description: $lineData['description'] ?? null,
                            debitAmount: $debit,
                            creditAmount: $credit,
                            createdAt: null,
                        );
                    }

                    if (bccomp($totalDebit, $totalCredit, 4) !== 0) {
                        throw new \DomainException(
                            "Journal entry is not balanced: total debit ({$totalDebit}) does not equal total credit ({$totalCredit})."
                        );
                    }

                    $entryNumber = $this->journalEntryRepository->nextEntryNumber($cmd->tenantId);

                    $entry = new JournalEntry(
                        id: null,
                        tenantId: $cmd->tenantId,
                        entryNumber: $entryNumber,
                        entryDate: $cmd->entryDate,
                        reference: $cmd->reference,
                        description: $cmd->description,
                        currency: $cmd->currency,
                        status: JournalEntryStatus::Draft,
                        totalDebit: $totalDebit,
                        totalCredit: $totalCredit,
                        lines: $lines,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->journalEntryRepository->save($entry);
                });
        });
    }
}
