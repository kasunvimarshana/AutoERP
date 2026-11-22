<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Vehicle\DTOs;

use Illuminate\Http\UploadedFile;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;

final readonly class VehicleDocumentData
{
    public function __construct(
        public VehicleDocumentType $documentType,
        public ?string $documentNumber = null,
        public ?string $issuedDate = null,
        public ?string $expiryDate = null,
        public ?string $filePath = null,
        public ?UploadedFile $file = null,
        public VehicleDocumentStatus $status = VehicleDocumentStatus::Pending,
        public ?string $notes = null,
    ) {}
}
