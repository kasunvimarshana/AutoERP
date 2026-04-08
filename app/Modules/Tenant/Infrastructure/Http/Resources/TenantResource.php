<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenant\Domain\Entities\Tenant;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->resource;

        return [
            'id'         => $tenant->id,
            'name'       => $tenant->name,
            'domain'     => $tenant->domain,
            'slug'       => $tenant->slug,
            'status'     => $tenant->status,
            'plan'       => $tenant->plan,
            'settings'   => $tenant->settings,
            'metadata'   => $tenant->metadata,
            'created_at' => $tenant->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $tenant->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
