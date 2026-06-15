<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalExpenseFinancialTreatment;
use Modules\VehicleRental\Enums\RentalExpenseType;

final readonly class RentalExpenseData
{
    /**
     * @param  list<string>|null  $attachments
     */
    public function __construct(
        public RentalExpenseType $expenseType,
        public string $expenseDate,
        public string $amount,
        public RentalExpenseFinancialTreatment $financialTreatment,
        public ?int $usageLogId = null,
        public ?int $currencyId = null,
        public ?int $taxGroupId = null,
        public ?string $originalNetAmount = null,
        public ?int $originalTaxGroupId = null,
        public string $originalTaxAmount = '0.000000',
        public ?string $originalGrossAmount = null,
        public string $originalWithholdingAmount = '0.000000',
        public string $recoverableInputTaxAmount = '0.000000',
        public ?string $recoveryBaseAmount = null,
        public ?int $recoveryTaxGroupId = null,
        public string $recoveryTaxAmount = '0.000000',
        public string $recoveryWithholdingAmount = '0.000000',
        public string $markupAmount = '0.000000',
        public ?int $responsiblePartyId = null,
        public ?string $receiptNo = null,
        public ?string $referenceNo = null,
        public ?string $description = null,
        public ?array $attachments = null,
        public ?int $createdBy = null,
    ) {}
}
