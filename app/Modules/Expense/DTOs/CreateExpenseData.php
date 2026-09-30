<?php

declare(strict_types=1);

namespace Modules\Expense\DTOs;

final readonly class CreateExpenseData
{
    public function __construct(
        public int $tenantId,
        public int $organizationUnitId,
        public int $expenseTypeId,
        public int $paymentMethodId,
        public string $expenseDate,
        public string $amount,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $referenceNumber = null,
        public ?string $instrumentNumber = null,
        public ?string $instrumentDate = null,
        public ?string $externalBankName = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public ?string $idempotencyKey = null,
    ) {}
}
