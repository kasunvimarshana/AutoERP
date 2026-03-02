<?php

declare(strict_types=1);

namespace Modules\Customization\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customization\Domain\Entities\CustomFieldValue;

class CustomFieldValueResource extends JsonResource
{
    public function __construct(private readonly CustomFieldValue $value) { parent::__construct($value); }

    public function toArray($request): array
    {
        return [
            'id' => $this->value->id,
            'tenant_id' => $this->value->tenantId,
            'entity_type' => $this->value->entityType,
            'entity_id' => $this->value->entityId,
            'field_id' => $this->value->fieldId,
            'value' => $this->value->value,
            'created_at' => $this->value->createdAt,
            'updated_at' => $this->value->updatedAt,
        ];
    }
}
