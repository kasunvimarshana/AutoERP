<?php

declare(strict_types=1);

namespace Modules\Settings\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateSettingData extends BaseDto
{
    public int $id;
    public ?string $groupKey = null;
    public ?string $settingKey = null;
    public ?string $label = null;
    public mixed $value = null;
    public mixed $defaultValue = null;
    public ?string $settingType = null;
    public ?string $description = null;
    public ?bool $isSystem = null;
    public ?bool $isEditable = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'           => 'required|integer',
            'groupKey'     => 'sometimes|required|string|max:100',
            'settingKey'   => 'sometimes|required|string|max:100',
            'label'        => 'sometimes|required|string|max:255',
            'value'        => 'nullable',
            'defaultValue' => 'nullable',
            'settingType'  => 'sometimes|required|string|in:string,integer,float,boolean,json,array',
            'description'  => 'nullable|string',
            'isSystem'     => 'nullable|boolean',
            'isEditable'   => 'nullable|boolean',
            'metadata'     => 'nullable|array',
        ];
    }
}
