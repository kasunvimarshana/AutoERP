<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'code'             => 'required|string|max:100',
            'user_id'          => 'nullable|integer|exists:users,id',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'billing_address'  => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'date_of_birth'    => 'nullable|string|max:20',
            'loyalty_tier'     => 'nullable|string|in:bronze,silver,gold,platinum',
            'credit_limit'     => 'nullable|numeric|min:0',
            'payment_terms'    => 'nullable|string|max:100',
            'currency'         => 'nullable|string|size:3',
            'tax_number'       => 'nullable|string|max:100',
            'status'           => 'nullable|string|in:active,inactive,draft',
            'type'             => 'nullable|string|in:retail,wholesale,corporate,vip,other',
            'attributes'       => 'nullable|array',
            'metadata'         => 'nullable|array',
        ];
    }
}
