<?php

namespace Modules\Document\Domain\Entities;

class DocumentDefinition
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $documentTypeId,
        public int $version,
        public string $name,
        public array $headerSchema,
        public array $allowedItemTypes,
        public array $validationRules,
        public array $formLayout,
        public bool $isActive,
    ) {}
}
