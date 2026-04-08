<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\TransactionRule;
interface TransactionRuleServiceInterface {
    public function createRule(string $tenantId, array $data): TransactionRule;
    public function updateRule(string $tenantId, string $id, array $data): TransactionRule;
    public function deleteRule(string $tenantId, string $id): void;
    public function getRule(string $tenantId, string $id): TransactionRule;
    public function getAllRules(string $tenantId): array;
}
