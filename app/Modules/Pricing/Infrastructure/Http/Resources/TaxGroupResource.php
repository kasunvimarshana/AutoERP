<?php
namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pricing\Domain\Entities\TaxGroup;

class TaxGroupResource extends JsonResource
{
    public function __construct(private readonly TaxGroup $taxGroup) { parent::__construct($taxGroup); }

    public function toArray($request): array
    {
        return [
            'id'          => $this->taxGroup->id,
            'tenant_id'   => $this->taxGroup->tenantId,
            'name'        => $this->taxGroup->name,
            'code'        => $this->taxGroup->code,
            'is_active'   => $this->taxGroup->isActive,
            'description' => $this->taxGroup->description,
        ];
    }
}
