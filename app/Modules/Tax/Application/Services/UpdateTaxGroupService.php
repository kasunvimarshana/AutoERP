<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\UpdateTaxGroupServiceInterface;
use Modules\Tax\Application\DTOs\TaxGroupData;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class UpdateTaxGroupService extends BaseService implements UpdateTaxGroupServiceInterface
{
    public function __construct(private readonly TaxGroupRepositoryInterface $taxGroupRepository)
    {
        parent::__construct($taxGroupRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto = TaxGroupData::fromArray($data);

        $taxGroup = $this->taxGroupRepository->find($dto->id ?? 0);
        if (! $taxGroup) {
            throw new \InvalidArgumentException('Tax group not found.');
        }

        $taxGroup->update(
            name: $dto->name,
            description: $dto->description,
        );

        return $this->taxGroupRepository->save($taxGroup);
    }
}
