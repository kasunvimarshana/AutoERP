<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Accounting\Application\Commands\PostJournalEntryCommand;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;

class PostJournalEntryHandler extends BaseHandler
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(PostJournalEntryCommand $command): JournalEntry
    {
        return $this->transaction(function () use ($command): JournalEntry {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (PostJournalEntryCommand $cmd): JournalEntry {
                    $entry = $this->journalEntryRepository->findById($cmd->id, $cmd->tenantId);

                    if ($entry === null) {
                        throw new \DomainException("Journal entry with ID {$cmd->id} not found.");
                    }

                    if (! $entry->status->isPostable()) {
                        throw new \DomainException("Journal entry cannot be posted. Current status: {$entry->status->value}.");
                    }

                    $posted = new JournalEntry(
                        id: $entry->id,
                        tenantId: $entry->tenantId,
                        entryNumber: $entry->entryNumber,
                        entryDate: $entry->entryDate,
                        reference: $entry->reference,
                        description: $entry->description,
                        currency: $entry->currency,
                        status: JournalEntryStatus::Posted,
                        totalDebit: $entry->totalDebit,
                        totalCredit: $entry->totalCredit,
                        lines: $entry->lines,
                        createdAt: $entry->createdAt,
                        updatedAt: null,
                    );

                    $saved = $this->journalEntryRepository->save($posted);

                    foreach ($entry->lines as $line) {
                        $account = $this->accountRepository->findById($line->accountId, $cmd->tenantId);

                        if ($account === null) {
                            continue;
                        }

                        $normalBalance = $account->type->normalBalance();

                        if (bccomp($line->debitAmount, '0', 4) > 0) {
                            // Debit entry: increases balance for debit-normal accounts (Asset/Expense),
                            // decreases for credit-normal accounts (Liability/Equity/Revenue)
                            $isIncrease = $normalBalance === 'debit';
                            $this->accountRepository->updateBalance($line->accountId, $cmd->tenantId, $line->debitAmount, $isIncrease);
                        }

                        if (bccomp($line->creditAmount, '0', 4) > 0) {
                            // Credit entry: increases balance for credit-normal accounts (Liability/Equity/Revenue),
                            // decreases for debit-normal accounts (Asset/Expense)
                            $isIncrease = $normalBalance === 'credit';
                            $this->accountRepository->updateBalance($line->accountId, $cmd->tenantId, $line->creditAmount, $isIncrease);
                        }
                    }

                    return $saved;
                });
        });
    }
}
