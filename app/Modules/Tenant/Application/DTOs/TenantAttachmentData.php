<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TenantAttachmentData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public string $file_path;

    public string $mime_type;

    public int $size;

    public ?string $type;

    public ?array $metadata;

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string',
            'file_path' => 'required|string',
            'mime_type' => 'required|string',
            'size' => 'required|integer|min:0',
            'type' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
