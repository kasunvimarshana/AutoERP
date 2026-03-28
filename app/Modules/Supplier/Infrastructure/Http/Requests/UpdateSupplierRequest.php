<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'code'           => 'required|string|max:100',
            'user_id'        => 'nullable|integer|exists:users,id',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|array',
            'contact_person' => 'nullable|array',
            'payment_terms'  => 'nullable|string|max:100',
            'currency'       => 'nullable|string|size:3',
            'tax_number'     => 'nullable|string|max:100',
            'status'         => 'nullable|string|in:active,inactive,draft',
            'type'           => 'nullable|string|in:manufacturer,distributor,retailer,other',
            'attributes'     => 'nullable|array',
            'metadata'       => 'nullable|array',
        ];
    }
}
