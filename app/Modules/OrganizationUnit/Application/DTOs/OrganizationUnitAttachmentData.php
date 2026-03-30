<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO carrying the processed (post-storage) fields for an attachment record.
 *
 * Populated by upload and replace services after the file has been persisted
 * to storage, and used to build the OrganizationUnitAttachment domain entity
 * that is then saved to the repository.
 *
 * Consistent with TenantAttachmentData in the Tenant module.
 */
class OrganizationUnitAttachmentData extends BaseDto
{
    public int $organization_unit_id;

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
            'organization_unit_id' => 'required|integer',
            'tenant_id'            => 'required|integer',
            'name'                 => 'required|string',
            'file_path'            => 'required|string',
            'mime_type'            => 'required|string',
            'size'                 => 'required|integer|min:0',
            'type'                 => 'nullable|string',
            'metadata'             => 'nullable|array',
        ];
    }
}
