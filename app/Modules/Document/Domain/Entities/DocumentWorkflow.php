<?php

namespace Modules\Document\Domain\Entities;

class DocumentWorkflow
{
    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @param  array<int, array<string, mixed>>  $transitions
     */
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $documentTypeId,
        public string $name,
        public bool $isDefault,
        public bool $isActive,
        public array $steps,
        public array $transitions,
    ) {
    }
}
