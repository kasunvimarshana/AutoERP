<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

use Modules\Customer\Enums\CustomerDocumentStatus;
use Modules\Customer\Enums\CustomerDocumentType;

final readonly class CustomerDocumentData
{
    public function __construct(
        public CustomerDocumentType $documentType,
        public ?string $documentNumber = null,
        public ?string $issuedDate = null,
        public ?string $expiryDate = null,
        public ?string $filePath = null,
        public CustomerDocumentStatus $status = CustomerDocumentStatus::Pending,
        public ?string $notes = null,
    ) {}
}
