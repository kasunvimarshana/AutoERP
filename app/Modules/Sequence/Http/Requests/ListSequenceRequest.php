<?php

declare(strict_types=1);

namespace Modules\Sequence\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Illuminate\Support\Facades\Auth;

final class ListSequenceRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('organization_units', 'id')],
            'document_type' => ['nullable', 'string', 'max:255'],
            'period_type' => ['nullable', 'string', 'in:yearly,monthly,infinite'],
            'period_value' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:'.(int) config('sequence.pagination.max_per_page', 200),
            ],
        ];
    }
}
