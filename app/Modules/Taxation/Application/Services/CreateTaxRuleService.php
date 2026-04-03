<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Taxation\Application\Contracts\CreateTaxRuleServiceInterface;
use Modules\Taxation\Application\DTOs\TaxRuleData;
use Modules\Taxation\Domain\Entities\TaxRule;
use Modules\Taxation\Domain\Events\TaxRuleCreated;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class CreateTaxRuleService extends BaseService implements CreateTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): TaxRule
    {
        $dto = TaxRuleData::fromArray($data);

        $taxRule = new TaxRule(
            tenantId: $dto->tenantId,
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
        $this->addEvent(new TaxRuleCreated($saved));

        return $saved;
    }
}
