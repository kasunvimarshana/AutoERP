<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ForfeitRentalDepositRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_requirement_version' => ['required', 'integer', 'min:1'],
            'invoice_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
        ];
    }
}
