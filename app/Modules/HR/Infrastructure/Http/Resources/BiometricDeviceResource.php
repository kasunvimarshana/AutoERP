<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\BiometricDevice;

class BiometricDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var BiometricDevice $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'device_type' => $entity->getDeviceType(),
            'ip_address' => $entity->getIpAddress(),
            'port' => $entity->getPort(),
            'location' => $entity->getLocation(),
            'org_unit_id' => $entity->getOrgUnitId(),
            'status' => $entity->getStatus()->value,
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
