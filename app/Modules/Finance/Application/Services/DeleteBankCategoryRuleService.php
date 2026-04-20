<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteBankCategoryRuleServiceInterface;
use Modules\Finance\Domain\Exceptions\BankCategoryRuleNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankCategoryRuleRepositoryInterface;

class DeleteBankCategoryRuleService extends BaseService implements DeleteBankCategoryRuleServiceInterface
{
    public function __construct(private readonly BankCategoryRuleRepositoryInterface $bankCategoryRuleRepository)
    {
        parent::__construct($bankCategoryRuleRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->bankCategoryRuleRepository->find($id)) {
            throw new BankCategoryRuleNotFoundException($id);
        }

        return $this->bankCategoryRuleRepository->delete($id);
    }
}
