<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\PayrollItem;

class PayrollItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PayrollItem $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'type' => $entity->getType(),
            'calculation_type' => $entity->getCalculationType(),
            'value' => $entity->getValue(),
            'is_active' => $entity->isActive(),
            'is_taxable' => $entity->isTaxable(),
            'account_id' => $entity->getAccountId(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
