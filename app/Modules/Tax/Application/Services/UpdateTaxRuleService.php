<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
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

        if ($dto->rowVersion !== $taxRule->getRowVersion()) {
            throw new ConcurrentModificationException('TaxRule', $dto->id ?? 0);
        }

        $taxRule->update(
            taxGroupId: $dto->taxGroupId,
            productCategoryId: $dto->productCategoryId,
            partyType: $dto->partyType,
            region: $dto->region,
            priority: $dto->priority,
        );

        return $this->taxRuleRepository->save($taxRule);
    }
}
