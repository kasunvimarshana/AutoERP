<?php

declare(strict_types=1);

namespace Modules\Brand\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class BrandData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public string $slug;

    public ?string $description;

    public ?string $website;

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
            'slug'        => 'required|string|max:255|regex:/^[a-z0-9\-]+$/',
            'description' => 'nullable|string',
            'website'     => 'nullable|string|url|max:255',
            'status'      => 'nullable|string|in:active,inactive,draft',
            'attributes'  => 'nullable|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
