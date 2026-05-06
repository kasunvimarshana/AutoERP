<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNumberingSequenceRequest extends FormRequest
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
            'row_version' => ['required', 'integer', 'min:1'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'module' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:255'],
            'prefix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'suffix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'padding' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
