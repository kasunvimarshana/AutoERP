<?php

declare(strict_types=1);

namespace Modules\Customization\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customization\Domain\Entities\CustomField;

class CustomFieldResource extends JsonResource
{
    public function __construct(private readonly CustomField $field) { parent::__construct($field); }

    public function toArray($request): array
    {
        return [
            'id' => $this->field->id,
            'tenant_id' => $this->field->tenantId,
            'entity_type' => $this->field->entityType,
            'field_key' => $this->field->fieldKey,
            'field_label' => $this->field->fieldLabel,
            'field_type' => $this->field->fieldType,
            'is_required' => $this->field->isRequired,
            'default_value' => $this->field->defaultValue,
            'sort_order' => $this->field->sortOrder,
            'options' => $this->field->options,
            'validation_rules' => $this->field->validationRules,
            'created_at' => $this->field->createdAt,
            'updated_at' => $this->field->updatedAt,
        ];
    }
}
