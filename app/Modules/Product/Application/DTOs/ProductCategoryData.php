<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductCategoryData extends BaseDto
{
    public ?string $id = null;
    public ?string $parentId = null;
    public string $code = '';
    public string $name = '';
    public ?string $description = null;
    public ?string $imagePath = null;
    public int $sortOrder = 0;
    public bool $isActive = true;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'code'      => ['required', 'string', 'max:50'],
            'name'      => ['required', 'string', 'max:200'],
            'parent_id' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
