<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class CategoryData extends BaseDto
{
    public ?string $name = null;
    public ?string $slug = null;
    public ?int $tenant_id = null;
    public ?int $parent_id = null;
    public ?string $description = null;
    public ?string $image_path = null;
    public ?bool $is_active = null;
    public ?int $sort_order = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255'],
            'tenant_id'   => ['sometimes', 'integer'],
            'parent_id'   => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'image_path'  => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
