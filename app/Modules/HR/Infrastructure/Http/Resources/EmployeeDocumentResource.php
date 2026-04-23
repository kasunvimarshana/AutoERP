<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\EmployeeDocument;

class EmployeeDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var EmployeeDocument $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'document_type' => $entity->getDocumentType(),
            'title' => $entity->getTitle(),
            'description' => $entity->getDescription(),
            'file_path' => $entity->getFilePath(),
            'mime_type' => $entity->getMimeType(),
            'file_size' => $entity->getFileSize(),
            'issued_date' => $entity->getIssuedDate()?->format('Y-m-d'),
            'expiry_date' => $entity->getExpiryDate()?->format('Y-m-d'),
            'is_expired' => $entity->isExpired(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
