<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class EmployeeDocumentData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly string $documentType,
        public readonly string $title,
        public readonly string $filePath,
        public readonly string $mimeType,
        public readonly int $fileSize,
        public readonly string $description = '',
        public readonly ?string $issuedDate = null,
        public readonly ?string $expiryDate = null,
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            documentType: (string) $data['document_type'],
            title: (string) $data['title'],
            filePath: (string) $data['file_path'],
            mimeType: (string) $data['mime_type'],
            fileSize: (int) $data['file_size'],
            description: isset($data['description']) ? (string) $data['description'] : '',
            issuedDate: isset($data['issued_date']) ? (string) $data['issued_date'] : null,
            expiryDate: isset($data['expiry_date']) ? (string) $data['expiry_date'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'document_type' => $this->documentType,
            'title' => $this->title,
            'description' => $this->description,
            'file_path' => $this->filePath,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'issued_date' => $this->issuedDate,
            'expiry_date' => $this->expiryDate,
            'metadata' => $this->metadata,
        ];
    }
}
