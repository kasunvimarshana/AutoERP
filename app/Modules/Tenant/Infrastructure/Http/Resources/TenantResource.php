<?php
declare(strict_types=1);
namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenant\Domain\Entities\Tenant;

class TenantResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->resource;
        return [
            'id' => $tenant->getId(),
            'name' => $tenant->getName(),
            'slug' => $tenant->getSlug(),
            'status' => $tenant->getStatus(),
            'plan_type' => $tenant->getPlanType(),
            'settings' => $tenant->getSettings(),
            'trial_ends_at' => $tenant->getTrialEndsAt()?->format('Y-m-d H:i:s'),
            'created_at' => $tenant->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $tenant->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
