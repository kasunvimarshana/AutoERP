<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RentalAgreementFormLookupRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [];
    }
}
