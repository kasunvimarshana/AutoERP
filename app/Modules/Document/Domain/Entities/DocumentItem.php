<?php

namespace Modules\Document\Domain\Entities;

class DocumentItem
{
    public function __construct(
        public ?int $id,
        public int $documentId,
        public string $itemType,
        public ?string $description,
        public string $lineTotal,
        public int $sequence,
        public array $data,
    ) {
    }
}
