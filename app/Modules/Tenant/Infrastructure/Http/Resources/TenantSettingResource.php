<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'key' => $this->getKey(),
            'value' => $this->getValue(),
            'group' => $this->getGroup(),
            'is_public' => $this->isPublic(),
            'created_at' => $this->getCreatedAt()?->format('c'),
            'updated_at' => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
