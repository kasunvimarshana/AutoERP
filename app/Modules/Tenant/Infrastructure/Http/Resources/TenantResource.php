<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class TenantResource extends JsonResource
{
    public function toArray($request): array
    {
        $storage = app(FileStorageServiceInterface::class);

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'domain' => $this->getDomain(),
            'logo_url' => $this->getLogoPath() ? $storage->url($this->getLogoPath()) : null,
            'database_config' => $this->getDatabaseConfig()->toArray(),
            'mail_config' => $this->getMailConfig()?->toArray(),
            'cache_config' => $this->getCacheConfig()?->toArray(),
            'queue_config' => $this->getQueueConfig()?->toArray(),
            'feature_flags' => $this->getFeatureFlags()->toArray(),
            'api_keys' => $this->getApiKeys()->toArray(),
            'settings' => $this->getSettings(),
            'plan' => $this->getPlan(),
            'tenant_plan_id' => $this->getTenantPlanId(),
            'status' => $this->getStatus(),
            'trial_ends_at' => $this->getTrialEndsAt()?->format('Y-m-d H:i:s'),
            'subscription_ends_at' => $this->getSubscriptionEndsAt()?->format('Y-m-d H:i:s'),
            'active' => $this->isActive(),
            'created_at' => $this->getCreatedAt()?->format('c'),
            'updated_at' => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
