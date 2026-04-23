<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\PerformanceCycle;

class PerformanceCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PerformanceCycle $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'name' => $entity->getName(),
            'period_start' => $entity->getPeriodStart()->format('Y-m-d'),
            'period_end' => $entity->getPeriodEnd()->format('Y-m-d'),
            'is_active' => $entity->isActive(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
