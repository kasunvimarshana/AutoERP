<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\RepositoryInterfaces;
use Modules\Accounting\Domain\Entities\TransactionRule;
interface TransactionRuleRepositoryInterface {
    public function findById(string $tenantId, string $id): ?TransactionRule;
    public function findAll(string $tenantId): array;
    public function findActive(string $tenantId): array;
    public function save(TransactionRule $rule): void;
    public function delete(string $tenantId, string $id): void;
}
