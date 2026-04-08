<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Accounting\Application\Contracts\TransactionRuleServiceInterface;
use Modules\Accounting\Domain\Entities\TransactionRule;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;
class TransactionRuleService implements TransactionRuleServiceInterface
{
    public function __construct(
        private readonly TransactionRuleRepositoryInterface $ruleRepository,
    ) {}
    public function getRule(string $tenantId, string $id): TransactionRule
    {
        $rule = $this->ruleRepository->findById($tenantId, $id);
        if ($rule === null) {
            throw new NotFoundException("Transaction rule [{$id}] not found.");
        }
        return $rule;
    }
    public function createRule(string $tenantId, array $data): TransactionRule
    {
        return DB::transaction(function () use ($tenantId, $data): TransactionRule {
            $now = now();
            $rule = new TransactionRule(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                priority: (int) ($data['priority'] ?? 100),
                conditions: (array) ($data['conditions'] ?? []),
                applyTo: $data['apply_to'] ?? 'all',
                accountId: $data['account_id'],
                description: $data['description'] ?? null,
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );
            $this->ruleRepository->save($rule);
            return $rule;
        });
    }
    public function updateRule(string $tenantId, string $id, array $data): TransactionRule
    {
        return DB::transaction(function () use ($tenantId, $id, $data): TransactionRule {
            $existing = $this->getRule($tenantId, $id);
            $updated = new TransactionRule(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                priority: (int) ($data['priority'] ?? $existing->priority),
                conditions: (array) ($data['conditions'] ?? $existing->conditions),
                applyTo: $data['apply_to'] ?? $existing->applyTo,
                accountId: $data['account_id'] ?? $existing->accountId,
                description: $data['description'] ?? $existing->description,
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->ruleRepository->save($updated);
            return $updated;
        });
    }
    public function deleteRule(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getRule($tenantId, $id);
            $this->ruleRepository->delete($tenantId, $id);
        });
    }
    public function getAllRules(string $tenantId): array
    {
        return $this->ruleRepository->findAll($tenantId);
    }
}
