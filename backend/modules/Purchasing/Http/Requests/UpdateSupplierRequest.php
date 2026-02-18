<?php

declare(strict_types=1);

namespace Modules\Purchasing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implement permission check
    }

    public function rules(): array
    {
        $supplierId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', "unique:suppliers,code,{$supplierId}"],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,suspended,blocked'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
