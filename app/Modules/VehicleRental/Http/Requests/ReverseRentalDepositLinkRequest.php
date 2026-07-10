<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ReverseRentalDepositLinkRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_requirement_version' => ['required', 'integer', 'min:1'],
            'expected_payment_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
