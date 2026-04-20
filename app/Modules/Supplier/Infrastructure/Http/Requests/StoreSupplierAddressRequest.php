<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:billing,shipping,other',
            'label' => 'nullable|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:50',
            'country_id' => 'required|integer|exists:countries,id',
            'is_default' => 'nullable|boolean',
            'geo_lat' => 'nullable|numeric|between:-90,90',
            'geo_lng' => 'nullable|numeric|between:-180,180',
        ];
    }
}
