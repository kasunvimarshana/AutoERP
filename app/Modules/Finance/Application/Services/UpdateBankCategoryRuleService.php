<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Application\Contracts\UpdateBankCategoryRuleServiceInterface;
use Modules\Finance\Application\DTOs\BankCategoryRuleData;
use Modules\Finance\Domain\Entities\BankCategoryRule;
use Modules\Finance\Domain\Exceptions\BankCategoryRuleNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankCategoryRuleRepositoryInterface;

class UpdateBankCategoryRuleService extends BaseService implements UpdateBankCategoryRuleServiceInterface
{
    public function __construct(private readonly BankCategoryRuleRepositoryInterface $bankCategoryRuleRepository)
    {
        parent::__construct($bankCategoryRuleRepository);
    }

    protected function handle(array $data): BankCategoryRule
    {
        $dto = BankCategoryRuleData::fromArray($data);
        /** @var BankCategoryRule|null $rule */
        $rule = $this->bankCategoryRuleRepository->find((int) $dto->id);
        if (! $rule) {
            throw new BankCategoryRuleNotFoundException((int) $dto->id);
        }
        if ($dto->row_version !== $rule->getRowVersion()) {
            throw new ConcurrentModificationException('BankCategoryRule', (int) $dto->id);
        }
        $rule->update(
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
