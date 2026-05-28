<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertEmployeeContactRequest extends FormRequest
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
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'metadata' => ['nullable', 'array'],
            'contact_type' => ['nullable', 'string', 'max:50'],
            'contact_name' => array_merge($required, ['string', 'max:180']),
            'relationship' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'is_emergency' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
