<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertOrganizationUnitLegalProfileRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['nullable', 'integer', 'min:1'],
            'legal_name' => ['required', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:150'],
            'vat_registration_number' => ['nullable', 'string', 'max:150'],
            'svat_registration_number' => ['nullable', 'string', 'max:150'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
        ];
    }
}
