<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostingProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $organizationUnitId = $this->organization_unit_id === null
            ? null
            : (int) $this->organization_unit_id;

        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'organization_unit_id' => $organizationUnitId,
            'scope' => $organizationUnitId === null ? 'tenant_default' : 'organization',
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'rules' => PostingProfileRuleResource::collection($this->whenLoaded('rules')),
        ];
    }
}
