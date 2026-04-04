<?php
namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pricing\Domain\Entities\PriceList;

class PriceListResource extends JsonResource
{
    public function __construct(private readonly PriceList $priceList) { parent::__construct($priceList); }

    public function toArray($request): array
    {
        return [
            'id'          => $this->priceList->id,
            'tenant_id'   => $this->priceList->tenantId,
            'name'        => $this->priceList->name,
            'code'        => $this->priceList->code,
            'currency'    => $this->priceList->currency,
            'is_default'  => $this->priceList->isDefault,
            'is_active'   => $this->priceList->isActive,
            'valid_from'  => $this->priceList->validFrom,
            'valid_to'    => $this->priceList->validTo,
            'description' => $this->priceList->description,
        ];
    }
}
