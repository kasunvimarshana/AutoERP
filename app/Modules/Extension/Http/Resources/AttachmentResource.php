<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Extension\Enums\AttachmentPreviewStatus;
use Modules\Extension\Enums\AttachmentVisibility;
use Modules\Extension\Models\AttachmentModel;

final class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AttachmentModel) {
            return [];
        }

        $visibility = $this->visibility instanceof AttachmentVisibility
            ? $this->visibility->value
            : (string) $this->visibility;
        $previewStatus = $this->preview_status instanceof AttachmentPreviewStatus
            ? $this->preview_status->value
            : (string) $this->preview_status;

        return [
            'id' => (int) $this->getKey(),
            'uuid' => $this->uuid,
            'row_version' => (int) $this->row_version,
            'tenant_id' => (int) $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id === null
                ? null
                : (int) $this->organization_unit_id,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => (int) $this->attachable_id,
            'source_module' => $this->source_module,
            'source_reference' => $this->source_reference,
            'category' => $this->category,
            'visibility' => $visibility,
            'display_name' => $this->display_name,
            'original_file_name' => $this->original_file_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size_bytes' => (string) $this->size,
            'checksum_sha256' => $this->checksum_sha256,
            'description' => $this->description,
            'document_number' => $this->document_number,
            'tags' => $this->tags ?? [],
            'metadata' => $this->metadata ?? [],
            'issued_at' => $this->issued_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'version_group_uuid' => $this->version_group_uuid,
            'version_number' => (int) $this->version_number,
            'previous_version_id' => $this->previous_version_id === null
                ? null
                : (int) $this->previous_version_id,
            'is_current' => (bool) $this->is_current,
            'preview_status' => $previewStatus,
            'preview_available' => $previewStatus === AttachmentPreviewStatus::Ready->value,
            'download_url' => route('extension.attachments.download', ['attachment' => $this->getKey()]),
            'preview_url' => $previewStatus === AttachmentPreviewStatus::Ready->value
                ? route('extension.attachments.preview', ['attachment' => $this->getKey()])
                : null,
            'uploaded_by' => $this->uploaded_by === null ? null : (int) $this->uploaded_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
