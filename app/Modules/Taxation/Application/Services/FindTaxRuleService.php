<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Taxation\Application\Contracts\FindTaxRuleServiceInterface;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class FindTaxRuleService extends BaseService implements FindTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->taxRuleRepository->find($data['id'] ?? null);
    }
}
