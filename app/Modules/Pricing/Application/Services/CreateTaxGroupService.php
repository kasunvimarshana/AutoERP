<?php
namespace Modules\Pricing\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Pricing\Application\Contracts\CreateTaxGroupServiceInterface;
use Modules\Pricing\Application\DTOs\TaxGroupData;
use Modules\Pricing\Domain\Entities\TaxGroup;
use Modules\Pricing\Domain\Events\TaxGroupCreated;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class CreateTaxGroupService implements CreateTaxGroupServiceInterface
{
    public function __construct(private readonly TaxGroupRepositoryInterface $repository) {}

    public function execute(TaxGroupData $data): TaxGroup
    {
        $taxGroup = $this->repository->create([
            'tenant_id'   => $data->tenantId,
            'name'        => $data->name,
            'code'        => $data->code,
            'is_active'   => $data->isActive,
            'description' => $data->description,
        ]);

        Event::dispatch(new TaxGroupCreated($data->tenantId, $taxGroup->id));

        return $taxGroup;
    }
}
