<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Accounting\Application\Contracts\BankAccountServiceInterface;
use Modules\Accounting\Domain\Entities\BankAccount;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
class BankAccountService implements BankAccountServiceInterface
{
    public function __construct(
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
    ) {}
    public function getBankAccount(string $tenantId, string $id): BankAccount
    {
        $account = $this->bankAccountRepository->findById($tenantId, $id);
        if ($account === null) {
            throw new NotFoundException("Bank account [{$id}] not found.");
        }
        return $account;
    }
    public function createBankAccount(string $tenantId, array $data): BankAccount
    {
        return DB::transaction(function () use ($tenantId, $data): BankAccount {
            $now = now();
            $account = new BankAccount(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                accountId: $data['account_id'],
                name: $data['name'],
                accountType: $data['account_type'],
                bankName: $data['bank_name'] ?? null,
                accountNumber: $data['account_number'] ?? null,
                routingNumber: $data['routing_number'] ?? null,
                currencyCode: $data['currency_code'] ?? 'USD',
                currentBalance: (float) ($data['current_balance'] ?? 0.0),
                lastReconciledAt: null,
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );
            $this->bankAccountRepository->save($account);
            return $account;
        });
    }
    public function updateBankAccount(string $tenantId, string $id, array $data): BankAccount
    {
        return DB::transaction(function () use ($tenantId, $id, $data): BankAccount {
            $existing = $this->getBankAccount($tenantId, $id);
            $updated = new BankAccount(
                id: $existing->id,
                tenantId: $existing->tenantId,
                accountId: $data['account_id'] ?? $existing->accountId,
                name: $data['name'] ?? $existing->name,
                accountType: $data['account_type'] ?? $existing->accountType,
                bankName: $data['bank_name'] ?? $existing->bankName,
                accountNumber: $data['account_number'] ?? $existing->accountNumber,
                routingNumber: $data['routing_number'] ?? $existing->routingNumber,
                currencyCode: $data['currency_code'] ?? $existing->currencyCode,
                currentBalance: (float) ($data['current_balance'] ?? $existing->currentBalance),
                lastReconciledAt: $existing->lastReconciledAt,
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->bankAccountRepository->save($updated);
            return $updated;
        });
    }
    public function deleteBankAccount(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getBankAccount($tenantId, $id);
            $this->bankAccountRepository->delete($tenantId, $id);
        });
    }
    public function getAllBankAccounts(string $tenantId): array
    {
        return $this->bankAccountRepository->findAll($tenantId);
    }
}
