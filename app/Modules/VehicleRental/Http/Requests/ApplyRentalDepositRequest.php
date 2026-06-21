<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ApplyRentalDepositRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
        ];
    }
}
