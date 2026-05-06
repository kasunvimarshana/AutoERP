<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\UpdateTaxRuleServiceInterface;
use Modules\Tax\Application\DTOs\TaxRuleData;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class UpdateTaxRuleService extends BaseService implements UpdateTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto = TaxRuleData::fromArray($data);

        $taxRule = $this->taxRuleRepository->find($dto->id ?? 0);
        if (! $taxRule) {
            throw new \InvalidArgumentException('Tax rule not found.');
        }

        $taxRule->update(
            taxGroupId: $dto->tax_group_id,
            productCategoryId: $dto->product_category_id,
            partyType: $dto->party_type,
            region: $dto->region,
            priority: $dto->priority,
        );

        return $this->taxRuleRepository->save($taxRule);
    }
}
