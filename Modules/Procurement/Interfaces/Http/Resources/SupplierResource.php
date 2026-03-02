<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Procurement\Domain\Entities\Supplier;

class SupplierResource extends JsonResource
{
    public function __construct(private readonly Supplier $supplier)
    {
        parent::__construct($supplier);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->supplier->id,
            'tenant_id' => $this->supplier->tenantId,
            'name' => $this->supplier->name,
            'contact_name' => $this->supplier->contactName,
            'email' => $this->supplier->email,
            'phone' => $this->supplier->phone,
            'address' => $this->supplier->address,
            'status' => $this->supplier->status,
            'notes' => $this->supplier->notes,
            'created_at' => $this->supplier->createdAt,
            'updated_at' => $this->supplier->updatedAt,
        ];
    }
}
