<?php

namespace Modules\Logistics\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier_id'           => ['nullable', 'uuid'],
            'origin_address'       => ['required', 'string'],
            'destination_address'  => ['required', 'string'],
            'scheduled_date'       => ['required', 'date'],
            'weight'               => ['nullable', 'numeric', 'min:0'],
            'shipping_cost'        => ['nullable', 'numeric', 'min:0'],
            'notes'                => ['nullable', 'string'],
            'lines'                => ['required', 'array', 'min:1'],
            'lines.*.product_id'   => ['required', 'uuid'],
            'lines.*.product_name' => ['required', 'string', 'max:255'],
            'lines.*.quantity'     => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit'         => ['nullable', 'string', 'max:50'],
        ];
    }
}
