<?php

namespace Modules\ECommerce\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceECommerceOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_name'    => 'required|string|max:255',
            'customer_email'   => 'required|email|max:255',
            'customer_phone'   => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'tax_amount'       => 'nullable|numeric|min:0',
            'payment_method'   => 'nullable|string|max:100',
            'notes'            => 'nullable|string',
            'lines'            => 'required|array|min:1',
            'lines.*.product_listing_id' => 'nullable|string|uuid',
            'lines.*.product_name'       => 'required|string|max:255',
            'lines.*.unit_price'         => 'required|numeric|min:0',
            'lines.*.quantity'           => 'required|numeric|min:0.01',
            'lines.*.discount'           => 'nullable|numeric|min:0',
            'lines.*.tax_rate'           => 'nullable|numeric|min:0|max:100',
        ];
    }
}
