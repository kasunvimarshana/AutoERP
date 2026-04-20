<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'supplier_id' => $this->getSupplierId(),
            'name' => $this->getName(),
            'role' => $this->getRole(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'is_primary' => $this->isPrimary(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
