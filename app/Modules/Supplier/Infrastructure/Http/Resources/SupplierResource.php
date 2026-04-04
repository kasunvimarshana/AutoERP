<?php
namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Supplier\Domain\Entities\Supplier;

class SupplierResource extends JsonResource
{
    public function __construct(private readonly Supplier $supplier)
    {
        parent::__construct($supplier);
    }

    public function toArray($request): array
    {
        return [
            'id'         => $this->supplier->id,
            'tenant_id'  => $this->supplier->tenantId,
            'name'       => $this->supplier->name,
            'code'       => $this->supplier->code,
            'status'     => $this->supplier->status,
            'email'      => $this->supplier->email,
            'phone'      => $this->supplier->phone,
            'address'    => $this->supplier->address,
            'city'       => $this->supplier->city,
            'country'    => $this->supplier->country,
            'tax_number' => $this->supplier->taxNumber,
            'currency'   => $this->supplier->currency,
            'notes'      => $this->supplier->notes,
        ];
    }
}
