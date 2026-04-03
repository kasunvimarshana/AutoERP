<?php

declare(strict_types=1);

namespace Modules\Settings\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Settings\Domain\ValueObjects\SettingType;

class SettingData extends BaseDto
{
    public int $tenantId;
    public string $groupKey;
    public string $settingKey;
    public string $label;
    public mixed $value = null;
    public mixed $defaultValue = null;
    public string $settingType = SettingType::STRING;
    public ?string $description = null;
    public bool $isSystem = false;
    public bool $isEditable = true;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'     => 'required|integer',
            'groupKey'     => 'required|string|max:100',
            'settingKey'   => 'required|string|max:100',
            'label'        => 'required|string|max:255',
            'value'        => 'nullable',
            'defaultValue' => 'nullable',
            'settingType'  => 'required|string|in:string,integer,float,boolean,json,array',
            'description'  => 'nullable|string',
            'isSystem'     => 'boolean',
            'isEditable'   => 'boolean',
            'metadata'     => 'nullable|array',
        ];
    }
}
