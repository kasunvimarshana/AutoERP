<?php

namespace Modules\Document\Application\DTOs;

class CreateDocumentDTO
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public int $tenantId,
        public int $documentTypeId,
        public string $documentDate,
        public ?int $organizationUnitId = null,
        public ?int $ownerId = null,
        public ?int $partyId = null,
        public ?string $dueDate = null,
        public ?string $notes = null,
        public ?int $documentDefinitionId = null,
        public ?string $sourceModule = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $sourceReference = null,
        public ?string $title = null,
        public array $data = [],
        public array $items = [],
    ) {
    }
}
