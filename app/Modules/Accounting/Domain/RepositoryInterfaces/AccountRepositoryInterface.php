<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Modules\Accounting\Domain\Entities\Account;
interface AccountRepositoryInterface {
    public function findById(string $tenantId, string $id): ?Account;
    public function findByCode(string $tenantId, string $code): ?Account;
    public function findAll(string $tenantId): array;
    public function findByType(string $tenantId, string $type): array;
    public function findChildren(string $tenantId, string $parentId): array;
    public function save(Account $account): void;
    public function delete(string $tenantId, string $id): void;
}
