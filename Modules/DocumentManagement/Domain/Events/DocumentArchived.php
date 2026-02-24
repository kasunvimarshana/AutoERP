<?php

namespace Modules\DocumentManagement\Domain\Events;

class DocumentArchived
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $tenantId,
    ) {}
}
