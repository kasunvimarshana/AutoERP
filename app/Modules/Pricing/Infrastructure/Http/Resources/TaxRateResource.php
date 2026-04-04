<?php
namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pricing\Domain\Entities\TaxRate;

class TaxRateResource extends JsonResource
{
    public function __construct(private readonly TaxRate $taxRate) { parent::__construct($taxRate); }

    public function toArray($request): array
    {
        return [
            'id'          => $this->taxRate->id,
            'tenant_id'   => $this->taxRate->tenantId,
            'name'        => $this->taxRate->name,
            'code'        => $this->taxRate->code,
            'rate'        => $this->taxRate->rate,
            'type'        => $this->taxRate->type,
            'is_active'   => $this->taxRate->isActive,
            'description' => $this->taxRate->description,
            'region'      => $this->taxRate->region,
            'tax_class'   => $this->taxRate->taxClass,
        ];
    }
}
