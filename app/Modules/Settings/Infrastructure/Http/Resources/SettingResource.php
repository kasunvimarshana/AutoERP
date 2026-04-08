<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->getId(),
            'tenant_id'     => $this->getTenantId(),
            'group_key'     => $this->getGroupKey(),
            'setting_key'   => $this->getSettingKey(),
            'setting_type'  => $this->getSettingType(),
            'value'         => $this->getValue(),
            'default_value' => $this->getDefaultValue(),
            'label'         => $this->getLabel(),
            'description'   => $this->getDescription(),
            'is_system'     => $this->isSystem(),
            'is_editable'   => $this->isEditable(),
            'metadata'      => $this->getMetadata()->toArray(),
            'created_at'    => $this->getCreatedAt()->format('c'),
            'updated_at'    => $this->getUpdatedAt()->format('c'),
        ];
    }
}
