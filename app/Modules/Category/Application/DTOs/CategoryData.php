<?php

declare(strict_types=1);

namespace Modules\Category\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CategoryData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public string $slug;

    public ?string $description;

    public ?int $parent_id;

    public string $status;

    public ?array $attributes;

    public ?array $metadata;

    public function __construct()
    {
        $this->status = 'active';
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|regex:/^[a-z0-9\-]+$/',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|integer|exists:categories,id',
            'status'      => 'nullable|string|in:active,inactive,draft',
            'attributes'  => 'nullable|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
