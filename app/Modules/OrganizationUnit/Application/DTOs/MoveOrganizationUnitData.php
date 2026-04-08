<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class MoveOrganizationUnitData extends BaseDto
{
    public ?int $parent_id;

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:organization_units,id',
        ];
    }
}
