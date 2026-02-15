<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class ConvertLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lead = $this->route('lead');
        return $this->user()->can('update', $lead);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Optional customer overrides
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_code' => ['nullable', 'string', 'max:50', 'unique:customers,code'],
            'customer_group' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment_method' => ['nullable', 'in:cash,credit_card,bank_transfer,cheque,other'],
            'priority' => ['nullable', 'in:low,medium,high,vip'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_code.unique' => 'This customer code is already in use',
        ];
    }
}
