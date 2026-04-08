<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Accounting\Application\Contracts\AccountServiceInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Events\AccountCreated;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
class AccountService implements AccountServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}
    public function getAccount(string $tenantId, string $id): Account
    {
        $account = $this->accountRepository->findById($tenantId, $id);
        if ($account === null) {
            throw new NotFoundException("Account [{$id}] not found.");
        }
        return $account;
    }
    public function createAccount(string $tenantId, array $data): Account
    {
        return DB::transaction(function () use ($tenantId, $data): Account {
            $now      = now();
            $parentId = $data['parent_id'] ?? null;
            $level    = 1;
            $path     = '';
            if ($parentId !== null) {
                $parent = $this->accountRepository->findById($tenantId, $parentId);
                if ($parent !== null) {
                    $level = $parent->level + 1;
                    $path  = $parent->path . '/' . $parentId;
                }
            }
            $id = (string) Str::uuid();
            $account = new Account(
                id: $id,
                tenantId: $tenantId,
                parentId: $parentId,
                code: $data['code'],
                name: $data['name'],
                type: $data['type'],
                subType: $data['sub_type'] ?? '',
                normalBalance: $data['normal_balance'],
                currencyCode: $data['currency_code'] ?? 'USD',
                isActive: (bool) ($data['is_active'] ?? true),
                isLocked: (bool) ($data['is_locked'] ?? false),
                isSystemAccount: (bool) ($data['is_system_account'] ?? false),
                description: $data['description'] ?? null,
                path: $path !== '' ? $path . '/' . $id : $id,
                level: $level,
                createdAt: $now,
                updatedAt: $now,
            );
            $this->accountRepository->save($account);
            Event::dispatch(new AccountCreated($account));
            return $account;
        });
    }
    public function updateAccount(string $tenantId, string $id, array $data): Account
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Account {
            $existing = $this->getAccount($tenantId, $id);
            $updated = new Account(
                id: $existing->id,
                tenantId: $existing->tenantId,
                parentId: $existing->parentId,
                code: $data['code'] ?? $existing->code,
                name: $data['name'] ?? $existing->name,
                type: $data['type'] ?? $existing->type,
                subType: $data['sub_type'] ?? $existing->subType,
                normalBalance: $data['normal_balance'] ?? $existing->normalBalance,
                currencyCode: $data['currency_code'] ?? $existing->currencyCode,
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                isLocked: (bool) ($data['is_locked'] ?? $existing->isLocked),
                isSystemAccount: $existing->isSystemAccount,
                description: $data['description'] ?? $existing->description,
                path: $existing->path,
                level: $existing->level,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->accountRepository->save($updated);
            return $updated;
        });
    }
    public function deleteAccount(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getAccount($tenantId, $id);
            $this->accountRepository->delete($tenantId, $id);
        });
    }
    public function getAllAccounts(string $tenantId): array
    {
        return $this->accountRepository->findAll($tenantId);
    }
    public function getAccountsByType(string $tenantId, string $type): array
    {
        return $this->accountRepository->findByType($tenantId, $type);
    }
}
