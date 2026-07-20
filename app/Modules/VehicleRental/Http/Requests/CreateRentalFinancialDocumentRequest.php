<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Constants\VehicleRentalFinancialDocument;
use Modules\VehicleRental\DTOs\RentalFinancialDocumentData;

final class CreateRentalFinancialDocumentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'invoice_date' => ['required', 'date_format:Y-m-d'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): RentalFinancialDocumentData
    {
        return new RentalFinancialDocumentData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            invoiceDate: (string) $this->validated('invoice_date'),
            expectedVersion: (int) $this->validated('expected_version'),
            actorId: $this->currentUserId(),
            exchangeRate: (string) ($this->validated('exchange_rate')
                ?? VehicleRentalFinancialDocument::DEFAULT_EXCHANGE_RATE),
            notes: $this->validated('notes'),
        );
    }
}
