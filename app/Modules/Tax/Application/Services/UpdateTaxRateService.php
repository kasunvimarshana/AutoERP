<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Tax\Application\Contracts\UpdateTaxRateServiceInterface;
use Modules\Tax\Application\DTOs\TaxRateData;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class UpdateTaxRateService extends BaseService implements UpdateTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto = TaxRateData::fromArray($data);

        $taxRate = $this->taxRateRepository->find($dto->id ?? 0);
        if (! $taxRate) {
            throw new \InvalidArgumentException('Tax rate not found.');
        }

        if ($dto->row_version !== $taxRate->getRowVersion()) {
            throw new ConcurrentModificationException('TaxRate', $dto->id ?? 0);
        }

        $taxRate->update(
            name: $dto->name,
            rate: $dto->rate,
            type: $dto->type,
            accountId: $dto->account_id,
            isCompound: $dto->is_compound,
            isActive: $dto->is_active,
            validFrom: $this->toDate($dto->valid_from),
            validTo: $this->toDate($dto->valid_to),
        );

        return $this->taxRateRepository->save($taxRate);
    }

    private function toDate(?string $value): ?\DateTimeInterface
    {
        return $value !== null ? new \DateTimeImmutable($value) : null;
    }
}
