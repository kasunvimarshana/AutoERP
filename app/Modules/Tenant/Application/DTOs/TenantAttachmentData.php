<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * Data Transfer Object for tenant attachment operations.
 *
 * Encapsulates attachment metadata validation. Used when uploading,
 * updating, or retrieving file attachments for tenants.
 */
class TenantAttachmentData extends BaseDto
{
    /**
     * Attachment ID for updates; null for creation.
     */
    public ?int $id = null;

    /**
     * Tenant ID this attachment belongs to (required).
     */
    public int $tenantId;

    /**
     * Unique identifier (UUID v4).
     */
    public ?string $uuid = null;

    /**
     * Original filename (required).
     */
    public string $name;

    /**
     * Storage path on filesystem (required).
     */
    public string $filePath;

    /**
     * MIME type of file (required).
     */
    public string $mimeType;

    /**
     * File size in bytes (required).
     */
    public int $size = 0;

    /**
     * Attachment type classification (optional, e.g., 'logo', 'document').
     */
    public ?string $type = null;

    /**
     * Additional metadata stored as JSON (optional).
     */
    public ?array $metadata = null;

    /**
     * Validation rules for input data.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'tenantId' => 'required|integer|exists:tenants,id',
            'uuid' => 'nullable|string|uuid|unique:tenant_attachments,uuid',
            'name' => 'required|string|max:500',
            'filePath' => 'required|string|max:1000',
            'mimeType' => 'required|string|max:127',
            'size' => 'required|integer|min:0|max:10737418240', // 10GB max
            'type' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ];
    }
}
