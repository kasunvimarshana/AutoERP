<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class OrganizationUnitDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $values = $this->resource instanceof DataRecord ? $this->resource->toArray() : (is_array($this->resource) ? $this->resource : []);
        unset($values['object_key'], $values['active_name_hash'], $values['deleted_at']);
        return $values;
    }
}
