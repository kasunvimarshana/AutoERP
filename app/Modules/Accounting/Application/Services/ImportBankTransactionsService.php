<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Accounting\Application\Contracts\ImportBankTransactionsServiceInterface;
use Modules\Accounting\Domain\Entities\BankTransaction;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
class ImportBankTransactionsService implements ImportBankTransactionsServiceInterface
{
    public function __construct(
        private readonly BankTransactionRepositoryInterface $transactionRepository,
    ) {}
    public function import(string $tenantId, string $bankAccountId, array $transactions): array
    {
        return DB::transaction(function () use ($tenantId, $bankAccountId, $transactions): array {
            $imported = [];
            $now      = now();
            foreach ($transactions as $txData) {
                $reference = $txData['reference'] ?? null;
                if ($reference !== null) {
                    $existing = $this->transactionRepository->findByReference($tenantId, $bankAccountId, $reference);
                    if ($existing !== null) {
                        continue;
                    }
                }
                $tx = new BankTransaction(
                    id: (string) Str::uuid(),
                    tenantId: $tenantId,
                    bankAccountId: $bankAccountId,
                    date: new \DateTimeImmutable($txData['date']),
                    description: $txData['description'],
                    amount: (float) $txData['amount'],
                    type: $txData['type'],
                    status: 'pending',
                    source: 'import',
                    accountId: null,
                    journalEntryId: null,
                    reference: $reference,
                    metadata: $txData['metadata'] ?? null,
                    createdAt: $now,
                    updatedAt: $now,
                );
                $this->transactionRepository->save($tx);
                $imported[] = $tx;
            }
            return $imported;
        });
    }
}
