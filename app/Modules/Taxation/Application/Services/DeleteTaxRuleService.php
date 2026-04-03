<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Taxation\Application\Contracts\DeleteTaxRuleServiceInterface;
use Modules\Taxation\Domain\Exceptions\TaxRuleNotFoundException;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class DeleteTaxRuleService extends BaseService implements DeleteTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];

        $taxRule = $this->taxRuleRepository->find($id);
        if (!$taxRule) {
            throw new TaxRuleNotFoundException($id);
        }

        $this->taxRuleRepository->delete($id);

        return true;
    }
}
