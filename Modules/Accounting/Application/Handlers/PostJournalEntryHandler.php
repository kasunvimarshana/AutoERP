<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Handlers;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\Commands\PostJournalEntryCommand;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalLine;
use Modules\Accounting\Infrastructure\Models\Account as AccountModel;
use Modules\Accounting\Infrastructure\Models\JournalEntry as JournalEntryModel;
use Modules\Accounting\Infrastructure\Models\JournalLine as JournalLineModel;
class PostJournalEntryHandler {
    public function handle(PostJournalEntryCommand $command): JournalEntry {
        return DB::transaction(function () use ($command): JournalEntry {
            // Build domain lines for validation
            $domainLines = array_map(fn(array $l): JournalLine => new JournalLine(
                accountId: (int)$l['account_id'],
                description: $l['description'] ?? null,
                debitAmount: bcadd((string)($l['debit_amount'] ?? '0'), '0', 4),
                creditAmount: bcadd((string)($l['credit_amount'] ?? '0'), '0', 4),
            ), $command->lines);
            // Validate balance before persisting
            $totalDebits  = '0.0000';
            $totalCredits = '0.0000';
            foreach ($domainLines as $line) {
                $totalDebits  = bcadd($totalDebits, $line->getDebitAmount(), 4);
                $totalCredits = bcadd($totalCredits, $line->getCreditAmount(), 4);
            }
            if (bccomp($totalDebits, $totalCredits, 4) !== 0) {
                throw new \DomainException("Journal entry is unbalanced. Debits: {$totalDebits}, Credits: {$totalCredits}.");
            }
            // Acquire pessimistic lock on involved accounts
            $accountIds = array_map(fn(JournalLine $l): int => $l->getAccountId(), $domainLines);
            AccountModel::withoutGlobalScope('tenant')
                ->whereIn('id', $accountIds)
                ->where('tenant_id', $command->tenantId)
                ->lockForUpdate()
                ->get();
            // Generate entry number using entry date for consistency
            $entryDate = new DateTimeImmutable($command->entryDate);
            $year  = $entryDate->format('Y');
            $month = $entryDate->format('m');
            $count = JournalEntryModel::withoutGlobalScope('tenant')
                ->where('tenant_id', $command->tenantId)
                ->whereYear('entry_date', $year)
                ->whereMonth('entry_date', $month)
                ->count() + 1;
            $entryNumber = sprintf('JE-%d-%s%s-%05d', $command->tenantId, $year, $month, $count);
            // Persist journal entry
            $entryModel = JournalEntryModel::create([
                'tenant_id'      => $command->tenantId,
                'entry_number'   => $entryNumber,
                'entry_date'     => $command->entryDate,
                'description'    => $command->description,
                'reference_type' => $command->referenceType,
                'reference_id'   => $command->referenceId,
                'is_posted'      => true,
                'is_reversed'    => false,
                'posted_by'      => $command->postedBy,
            ]);
            // Persist lines and update account balances
            foreach ($domainLines as $line) {
                JournalLineModel::create([
                    'tenant_id'      => $command->tenantId,
                    'journal_entry_id'=> $entryModel->id,
                    'account_id'     => $line->getAccountId(),
                    'description'    => $line->getDescription(),
                    'debit_amount'   => $line->getDebitAmount(),
                    'credit_amount'  => $line->getCreditAmount(),
                ]);
                // Update account current_balance
                $account = AccountModel::withoutGlobalScope('tenant')
                    ->where('id', $line->getAccountId())
                    ->where('tenant_id', $command->tenantId)
                    ->first();
                if ($account) {
                    $currentBalance = (string)$account->current_balance;
                    // Resolve whether this account type has debit as normal balance
                    $accountType   = $account->type instanceof \Modules\Accounting\Domain\Enums\AccountType
                        ? $account->type
                        : \Modules\Accounting\Domain\Enums\AccountType::from((string)$account->type);
                    $isDebitNormal = in_array($accountType, [
                        \Modules\Accounting\Domain\Enums\AccountType::ASSET,
                        \Modules\Accounting\Domain\Enums\AccountType::EXPENSE,
                    ], true);
                    if ($line->isDebit()) {
                        $newBalance = $isDebitNormal
                            ? bcadd($currentBalance, $line->getDebitAmount(), 4)
                            : bcsub($currentBalance, $line->getDebitAmount(), 4);
                    } else {
                        $newBalance = $isDebitNormal
                            ? bcsub($currentBalance, $line->getCreditAmount(), 4)
                            : bcadd($currentBalance, $line->getCreditAmount(), 4);
                    }
                    $account->update(['current_balance' => $newBalance]);
                }
            }
            return new JournalEntry(
                id: (int)$entryModel->id,
                tenantId: $command->tenantId,
                entryNumber: $entryNumber,
                entryDate: new DateTimeImmutable($command->entryDate),
                description: $command->description,
                referenceType: $command->referenceType,
                referenceId: $command->referenceId,
                isPosted: true,
                isReversed: false,
                lines: $domainLines,
            );
        });
    }
}
