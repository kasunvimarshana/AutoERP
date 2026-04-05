<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;

use Modules\Accounting\Domain\Entities\TransactionRule;

interface TransactionRuleRepositoryInterface
{
    public function findById(int $id): ?TransactionRule;
    public function findActiveByTenant(int $tenantId): array;
    public function create(array $data): TransactionRule;
    public function update(int $id, array $data): ?TransactionRule;
    public function incrementMatchCount(int $id): void;
    public function delete(int $id): bool;
}
