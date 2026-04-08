<?php

declare(strict_types=1);

namespace Modules\Location\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class MoveLocationData extends BaseDto
{
    public ?int $parent_id;

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:locations,id',
        ];
    }
}
