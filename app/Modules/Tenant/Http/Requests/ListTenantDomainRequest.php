<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainStatus;

final class ListTenantDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:253'],
            'status' => ['nullable', 'string', Rule::in(TenantDomainStatus::values())],
            'ownership_status' => ['nullable', 'string', Rule::in(TenantDomainOwnershipStatus::values())],
            'operational_status' => ['nullable', 'string', Rule::in(TenantDomainOperationalStatus::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
