<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class OrganizationUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $values = $this->resource instanceof DataRecord ? $this->resource->toArray() : (is_array($this->resource) ? $this->resource : []);
        unset($values['logo_object_key'], $values['path_hash']);
        $values['lifecycle_status'] = ($values['retired_at'] ?? null) !== null
            ? 'retired'
            : ((bool) ($values['is_active'] ?? false) ? 'active' : 'inactive');
        return $values;
    }
}
