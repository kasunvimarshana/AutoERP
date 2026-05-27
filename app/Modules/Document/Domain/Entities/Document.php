<?php

namespace Modules\Document\Domain\Entities;

class Document
{
    public function __construct(
        public ?int $id,
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $documentTypeId,
        public string $documentNumber,
        public string $documentDate,
        public ?string $dueDate,
        public string $status,
        public ?int $ownerId,
        public ?int $partyId,
        public string $subtotal,
        public string $discountTotal,
        public string $taxTotal,
        public string $grandTotal,
        public array $data,
        public ?string $notes,
        public ?int $createdBy,
        public ?int $updatedBy,
        public array $attachments = [],
    ) {
    }
}
