<?php

namespace Modules\Logistics\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTrackingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_order_id' => ['required', 'uuid'],
            'event_type'        => ['required', 'string', 'in:picked_up,in_transit,out_for_delivery,delivered,failed_attempt,returned'],
            'location'          => ['nullable', 'string', 'max:255'],
            'description'       => ['required', 'string'],
            'occurred_at'       => ['required', 'date'],
        ];
    }
}
