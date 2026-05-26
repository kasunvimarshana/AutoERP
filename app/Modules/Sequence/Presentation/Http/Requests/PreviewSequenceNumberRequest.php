<?php

declare(strict_types=1);

namespace Modules\Sequence\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class PreviewSequenceNumberRequest extends FormRequest
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
            'tenant_id' => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'document_type' => ['required', 'string', 'max:255'],
            'period_type' => ['nullable', 'string', 'in:yearly,monthly,infinite'],
            'period_value' => ['nullable', 'string', 'max:255'],
            'at_date' => ['nullable', 'date'],
            'prefix' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'padding' => ['nullable', 'integer', 'min:1', 'max:18'],
            'next_number' => ['nullable', 'integer', 'min:1'],
            'tokens' => ['nullable', 'array'],
        ];
    }
}
