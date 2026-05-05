<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\BankCategoryRule;
use Modules\Finance\Domain\RepositoryInterfaces\BankCategoryRuleRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankCategoryRuleModel;

class EloquentBankCategoryRuleRepository extends EloquentRepository implements BankCategoryRuleRepositoryInterface
{
    public function __construct(BankCategoryRuleModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BankCategoryRuleModel $m): BankCategoryRule => $this->mapToDomain($m));
    }

    public function save(BankCategoryRule $rule): BankCategoryRule
    {
        $data = [
            'tenant_id' => $rule->getTenantId(),
            'bank_account_id' => $rule->getBankAccountId(),
            'name' => $rule->getName(),
            'priority' => $rule->getPriority(),
            'conditions' => $rule->getConditions(),
            'account_id' => $rule->getAccountId(),
            'description_template' => $rule->getDescriptionTemplate(),
            'is_active' => $rule->isActive(),
            'row_version' => $rule->getRowVersion(),
        ];

        $model = $rule->getId()
            ? $this->update($rule->getId(), $data)
            : $this->create($data);

        /** @var BankCategoryRuleModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(BankCategoryRuleModel $m): BankCategoryRule
    {
        return new BankCategoryRule(
            tenantId: (int) $m->tenant_id,
            name: (string) $m->name,
            conditions: (array) $m->conditions,
            accountId: (int) $m->account_id,
            bankAccountId: $m->bank_account_id !== null ? (int) $m->bank_account_id : null,
            priority: (int) $m->priority,
            descriptionTemplate: $m->description_template,
            isActive: (bool) $m->is_active,
            rowVersion: (int) ($m->row_version ?? 1),
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
