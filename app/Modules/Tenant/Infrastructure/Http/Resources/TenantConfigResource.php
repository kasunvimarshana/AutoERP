<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TenantConfigResource extends JsonResource
{
    public function toArray($request)
    {
        $resource = $this->resource;

        if (method_exists($resource, 'getDatabaseConfig')) {
            return [
                'id' => $resource->getId(),
                'database_config' => $resource->getDatabaseConfig()->toArray(),
                'mail_config' => $resource->getMailConfig()?->toArray(),
                'cache_config' => $resource->getCacheConfig()?->toArray(),
                'queue_config' => $resource->getQueueConfig()?->toArray(),
                'feature_flags' => $resource->getFeatureFlags()->toArray(),
                'api_keys' => $resource->getApiKeys()->toArray(),
                'active' => $resource->isActive(),
                'updated_at' => $resource->getUpdatedAt()->format('c'),
            ];
        }

        return parent::toArray($request);
    }
}
