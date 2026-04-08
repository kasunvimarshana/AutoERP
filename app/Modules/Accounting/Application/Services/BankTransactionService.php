<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Accounting\Application\Contracts\BankTransactionServiceInterface;
use Modules\Accounting\Domain\Entities\BankTransaction;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
class BankTransactionService implements BankTransactionServiceInterface
{
    public function __construct(
        private readonly BankTransactionRepositoryInterface $transactionRepository,
    ) {}
    public function getTransaction(string $tenantId, string $id): BankTransaction
    {
        $tx = $this->transactionRepository->findById($tenantId, $id);
        if ($tx === null) {
            throw new NotFoundException("Bank transaction [{$id}] not found.");
        }
        return $tx;
    }
    public function createTransaction(string $tenantId, array $data): BankTransaction
    {
        return DB::transaction(function () use ($tenantId, $data): BankTransaction {
            $now = now();
            $tx = new BankTransaction(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                bankAccountId: $data['bank_account_id'],
                date: new \DateTimeImmutable($data['date']),
                description: $data['description'],
                amount: (float) $data['amount'],
                type: $data['type'],
                status: $data['status'] ?? 'pending',
                source: $data['source'] ?? 'manual',
                accountId: $data['account_id'] ?? null,
                journalEntryId: $data['journal_entry_id'] ?? null,
                reference: $data['reference'] ?? null,
                metadata: $data['metadata'] ?? null,
                createdAt: $now,
                updatedAt: $now,
            );
            $this->transactionRepository->save($tx);
            return $tx;
        });
    }
    public function updateTransaction(string $tenantId, string $id, array $data): BankTransaction
    {
        return DB::transaction(function () use ($tenantId, $id, $data): BankTransaction {
            $existing = $this->getTransaction($tenantId, $id);
            $updated = new BankTransaction(
                id: $existing->id,
                tenantId: $existing->tenantId,
                bankAccountId: $existing->bankAccountId,
                date: isset($data['date']) ? new \DateTimeImmutable($data['date']) : $existing->date,
                description: $data['description'] ?? $existing->description,
                amount: (float) ($data['amount'] ?? $existing->amount),
                type: $data['type'] ?? $existing->type,
                status: $data['status'] ?? $existing->status,
                source: $existing->source,
                accountId: $data['account_id'] ?? $existing->accountId,
                journalEntryId: $existing->journalEntryId,
                reference: $data['reference'] ?? $existing->reference,
                metadata: $data['metadata'] ?? $existing->metadata,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->transactionRepository->save($updated);
            return $updated;
        });
    }
    public function deleteTransaction(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getTransaction($tenantId, $id);
            $this->transactionRepository->delete($tenantId, $id);
        });
    }
    public function getTransactions(string $tenantId, string $bankAccountId, array $filters = []): array
    {
        return $this->transactionRepository->findByBankAccount($tenantId, $bankAccountId, $filters);
    }
}
