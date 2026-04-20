<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\DeleteTaxRuleServiceInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class DeleteTaxRuleService extends BaseService implements DeleteTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): bool
    {
        return $this->taxRuleRepository->delete((int) $data['id']);
    }
}
