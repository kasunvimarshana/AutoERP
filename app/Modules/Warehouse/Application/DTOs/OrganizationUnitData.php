<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class OrganizationUnitData extends BaseDto
{
    public ?string $id = null;
    public ?string $parentId = null;
    public string $code = '';
    public string $name = '';
    public string $type = 'department';
    public ?string $description = null;
    public ?int $managerUserId = null;
    public bool $isActive = true;
    public int $sortOrder = 0;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', 'max:50'],
        ];
    }
}
