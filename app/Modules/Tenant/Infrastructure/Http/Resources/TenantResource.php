<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class TenantResource extends JsonResource
{
    public function toArray($request)
    {
        $storage = app(FileStorageServiceInterface::class);
        // $resource = $this->resource;

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'domain' => $this->getDomain(),
            'logo_url' => $this->getLogoPath() ? $storage->url($this->getLogoPath()) : null,
            'database_config' => $this->getDatabaseConfig()->toArray(),
            'mail_config' => $this->getMailConfig()?->toArray(),
            'cache_config' => $this->getCacheConfig()?->toArray(),
            'queue_config' => $this->getQueueConfig()?->toArray(),
            'feature_flags' => $this->getFeatureFlags()->toArray(),
            'api_keys' => $this->getApiKeys()->toArray(),
            'active' => $this->isActive(),
            'created_at' => $this->getCreatedAt()?->format('c'),
            'updated_at' => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
