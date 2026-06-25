<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class RetryFailedTenantDomainsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
