<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Taxation\Application\Contracts\UpdateTaxRuleServiceInterface;
use Modules\Taxation\Application\DTOs\UpdateTaxRuleData;
use Modules\Taxation\Domain\Entities\TaxRule;
use Modules\Taxation\Domain\Events\TaxRuleUpdated;
use Modules\Taxation\Domain\Exceptions\TaxRuleNotFoundException;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class UpdateTaxRuleService extends BaseService implements UpdateTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): TaxRule
    {
        $dto = UpdateTaxRuleData::fromArray($data);

        $taxRule = $this->taxRuleRepository->find($dto->id);
        if (!$taxRule) {
            throw new TaxRuleNotFoundException($dto->id);
        }

        $taxRule->updateDetails(
            name: $dto->name,
            taxRateId: $dto->taxRateId,
            entityType: $dto->entityType,
            entityId: $dto->entityId,
            jurisdiction: $dto->jurisdiction,
            priority: $dto->priority,
            isActive: $dto->isActive,
            description: $dto->description,
            metadata: $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->taxRuleRepository->save($taxRule);
        $this->addEvent(new TaxRuleUpdated($saved));

        return $saved;
    }
}
