<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\DTOs\BankCategoryRuleData;
use Modules\Finance\Domain\Entities\BankCategoryRule;
use Modules\Finance\Domain\RepositoryInterfaces\BankCategoryRuleRepositoryInterface;

class CreateBankCategoryRuleService extends BaseService implements CreateBankCategoryRuleServiceInterface
{
    public function __construct(private readonly BankCategoryRuleRepositoryInterface $bankCategoryRuleRepository)
    {
        parent::__construct($bankCategoryRuleRepository);
    }

    protected function handle(array $data): BankCategoryRule
    {
        $dto = BankCategoryRuleData::fromArray($data);

        $rule = new BankCategoryRule(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            conditions: $dto->conditions,
            accountId: $dto->account_id,
            bankAccountId: $dto->bank_account_id,
            priority: $dto->priority,
            descriptionTemplate: $dto->description_template,
            isActive: $dto->is_active,
        );

        return $this->bankCategoryRuleRepository->save($rule);
    }
}
