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
            tenantId: $dto->tenantId,
            name: $dto->name,
            conditions: $dto->conditions,
            accountId: $dto->accountId,
            bankAccountId: $dto->bankAccountId,
            priority: $dto->priority,
            descriptionTemplate: $dto->descriptionTemplate,
            isActive: $dto->isActive,
        );

        return $this->bankCategoryRuleRepository->save($rule);
    }
}
