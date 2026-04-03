<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Taxation\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Taxation\Application\DTOs\TaxRateData;
use Modules\Taxation\Domain\Entities\TaxRate;
use Modules\Taxation\Domain\Events\TaxRateCreated;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class CreateTaxRateService extends BaseService implements CreateTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): TaxRate
    {
        $dto = TaxRateData::fromArray($data);

        $taxRate = new TaxRate(
            tenantId: $dto->tenantId,
            name: $dto->name,
            code: $dto->code,
            taxType: $dto->taxType,
            rate: $dto->rate,
            calculationMethod: $dto->calculationMethod,
            jurisdiction: $dto->jurisdiction,
            isActive: $dto->isActive,
            description: $dto->description,
            effectiveFrom: $dto->effectiveFrom ? new \DateTimeImmutable($dto->effectiveFrom) : null,
            effectiveTo: $dto->effectiveTo ? new \DateTimeImmutable($dto->effectiveTo) : null,
            metadata: $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->taxRateRepository->save($taxRate);
        $this->addEvent(new TaxRateCreated($saved));

        return $saved;
    }
}
