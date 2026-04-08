<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'code'        => $this->getCode(),
            'name'        => $this->getName(),
            'type'        => $this->getType(),
            'subtype'     => $this->getSubtype(),
            'description' => $this->getDescription(),
            'currency'    => $this->getCurrency(),
            'balance'     => $this->getBalance(),
            'is_system'   => $this->isSystem(),
            'parent_id'   => $this->getParentId(),
            'status'      => $this->getStatus(),
            'attributes'  => $this->getAttributes(),
            'metadata'    => $this->getMetadata(),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
