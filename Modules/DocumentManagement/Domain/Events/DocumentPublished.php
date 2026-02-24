<?php

namespace Modules\DocumentManagement\Domain\Events;

class DocumentPublished
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $tenantId,
        public readonly string $title,
    ) {}
}
