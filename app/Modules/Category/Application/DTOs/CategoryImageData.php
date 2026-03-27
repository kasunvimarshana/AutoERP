<?php

declare(strict_types=1);

namespace Modules\Category\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CategoryImageData extends BaseDto
{
    public int $category_id;

    public ?array $file;

    public ?array $metadata;

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:categories,id',
            'file'        => 'required|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
