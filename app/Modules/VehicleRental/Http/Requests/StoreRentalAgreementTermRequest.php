<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class StoreRentalAgreementTermRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_agreement_version' => ['required', 'integer', 'min:1'],
            'term_code' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:150'],
            'content' => ['required', 'string'],
            'is_printable' => ['nullable', 'boolean'],
        ];
    }
}
