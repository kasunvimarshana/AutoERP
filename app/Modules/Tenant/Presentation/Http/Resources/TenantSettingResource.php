<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Application\DTO\DataRecord;

final class TenantSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->resource->toArray();
        }

        return is_array($this->resource) ? $this->resource : [];
    }
}
