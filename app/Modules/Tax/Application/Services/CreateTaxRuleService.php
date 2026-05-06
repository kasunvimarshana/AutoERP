<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\CreateTaxRuleServiceInterface;
use Modules\Tax\Application\DTOs\TaxRuleData;
use Modules\Tax\Domain\Entities\TaxRule;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class CreateTaxRuleService extends BaseService implements CreateTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): TaxRule
    {
        $dto = TaxRuleData::fromArray($data);

        return $this->taxRuleRepository->save(new TaxRule(
            tenantId: $dto->tenant_id,
            taxGroupId: $dto->tax_group_id,
            productCategoryId: $dto->product_category_id,
            partyType: $dto->party_type,
            region: $dto->region,
            priority: $dto->priority,
        ));
    }
}
