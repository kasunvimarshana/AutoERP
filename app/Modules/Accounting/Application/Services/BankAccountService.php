<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\BankAccountServiceInterface;
use Modules\Accounting\Domain\Entities\BankAccount;
use Modules\Accounting\Domain\Exceptions\BankAccountNotFoundException;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;

class BankAccountService implements BankAccountServiceInterface
{
    public function __construct(private readonly BankAccountRepositoryInterface $repo) {}

    public function findById(int $id): BankAccount
    {
        $account = $this->repo->findById($id);
        if (!$account) throw new BankAccountNotFoundException($id);
        return $account;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->repo->findByTenant($tenantId);
    }

    public function create(array $data): BankAccount
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): BankAccount
    {
        $account = $this->repo->update($id, $data);
        if (!$account) throw new BankAccountNotFoundException($id);
        return $account;
    }

    public function delete(int $id): bool
    {
        $account = $this->repo->findById($id);
        if (!$account) throw new BankAccountNotFoundException($id);
        return $this->repo->delete($id);
    }
}
