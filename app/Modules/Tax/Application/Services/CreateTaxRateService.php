<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Tax\Application\DTOs\TaxRateData;
use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class CreateTaxRateService extends BaseService implements CreateTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): TaxRate
    {
        $dto = TaxRateData::fromArray($data);

        return $this->taxRateRepository->save(new TaxRate(
            tenantId: $dto->tenant_id,
            taxGroupId: $dto->tax_group_id,
            name: $dto->name,
            rate: $dto->rate,
            type: $dto->type,
            accountId: $dto->account_id,
            isCompound: $dto->is_compound,
            isActive: $dto->is_active,
            validFrom: $this->toDate($dto->valid_from),
            validTo: $this->toDate($dto->valid_to),
        ));
    }

    private function toDate(?string $value): ?\DateTimeInterface
    {
        return $value !== null ? new \DateTimeImmutable($value) : null;
    }
}
