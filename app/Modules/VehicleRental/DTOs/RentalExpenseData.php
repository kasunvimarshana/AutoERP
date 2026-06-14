<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;

final readonly class RentalExpenseData
{
    /**
     * @param list<string>|null $attachments
     */
    public function __construct(
        public RentalExpenseType $expenseType,
        public string $amount,
        public bool $isBillable,
        public ?int $usageLogId = null,
        public ?string $receiptNo = null,
        public ?string $referenceNo = null,
        public ?string $description = null,
        public ?array $attachments = null,
        public RentalExpenseStatus $status = RentalExpenseStatus::Draft,
        public ?int $approvedBy = null,
    ) {}
}
