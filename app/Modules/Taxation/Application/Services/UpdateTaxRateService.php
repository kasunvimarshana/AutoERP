<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Taxation\Application\Contracts\UpdateTaxRateServiceInterface;
use Modules\Taxation\Application\DTOs\UpdateTaxRateData;
use Modules\Taxation\Domain\Entities\TaxRate;
use Modules\Taxation\Domain\Events\TaxRateUpdated;
use Modules\Taxation\Domain\Exceptions\TaxRateNotFoundException;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class UpdateTaxRateService extends BaseService implements UpdateTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): TaxRate
    {
        $dto = UpdateTaxRateData::fromArray($data);

        $taxRate = $this->taxRateRepository->find($dto->id);
        if (!$taxRate) {
            throw new TaxRateNotFoundException($dto->id);
        }

        $taxRate->updateDetails(
            name: $dto->name,
            code: $dto->code,
            taxType: $dto->taxType,
            calculationMethod: $dto->calculationMethod,
            rate: $dto->rate,
            jurisdiction: $dto->jurisdiction,
            isActive: $dto->isActive,
            description: $dto->description,
            effectiveFrom: $dto->effectiveFrom ? new \DateTimeImmutable($dto->effectiveFrom) : null,
            effectiveTo: $dto->effectiveTo ? new \DateTimeImmutable($dto->effectiveTo) : null,
            metadata: $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->taxRateRepository->save($taxRate);
        $this->addEvent(new TaxRateUpdated($saved));

        return $saved;
    }
}
