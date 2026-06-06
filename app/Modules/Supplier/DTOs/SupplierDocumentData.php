<?php

declare(strict_types=1);

namespace Modules\Supplier\DTOs;

use Modules\Supplier\Enums\SupplierDocumentStatus;
use Modules\Supplier\Enums\SupplierDocumentType;

final readonly class SupplierDocumentData
{
    public function __construct(
        public SupplierDocumentType $documentType,
        public ?string $documentNumber = null,
        public ?string $issuedDate = null,
        public ?string $expiryDate = null,
        public ?string $filePath = null,
        public SupplierDocumentStatus $status = SupplierDocumentStatus::Pending,
        public ?string $notes = null,
    ) {}
}
