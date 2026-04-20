<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNumberingSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'module' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:255'],
            'prefix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'suffix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'next_number' => ['sometimes', 'integer', 'min:1'],
            'padding' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
