<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\CreateTaxGroupServiceInterface;
use Modules\Tax\Application\DTOs\TaxGroupData;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class CreateTaxGroupService extends BaseService implements CreateTaxGroupServiceInterface
{
    public function __construct(private readonly TaxGroupRepositoryInterface $taxGroupRepository)
    {
        parent::__construct($taxGroupRepository);
    }

    protected function handle(array $data): TaxGroup
    {
        $dto = TaxGroupData::fromArray($data);

        return $this->taxGroupRepository->save(new TaxGroup(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            description: $dto->description,
        ));
    }
}
