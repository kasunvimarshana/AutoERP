<?php
namespace Modules\Pricing\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Events\PriceListCreated;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class CreatePriceListService implements CreatePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $repository) {}

    public function execute(PriceListData $data): PriceList
    {
        $priceList = $this->repository->create([
            'tenant_id'   => $data->tenantId,
            'name'        => $data->name,
            'code'        => $data->code,
            'currency'    => $data->currency,
            'is_default'  => $data->isDefault,
            'is_active'   => $data->isActive,
            'valid_from'  => $data->validFrom,
            'valid_to'    => $data->validTo,
            'description' => $data->description,
        ]);

        Event::dispatch(new PriceListCreated($data->tenantId, $priceList->id));

        return $priceList;
    }
}
