<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindBankCategoryRuleServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankCategoryRuleRepositoryInterface;

class FindBankCategoryRuleService extends BaseService implements FindBankCategoryRuleServiceInterface
{
    public function __construct(private readonly BankCategoryRuleRepositoryInterface $bankCategoryRuleRepository)
    {
        parent::__construct($bankCategoryRuleRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
