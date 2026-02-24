<?php

namespace Modules\FieldService\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'service_team_id' => 'nullable|string|uuid',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'customer_id'     => 'nullable|string|uuid',
            'contact_name'    => 'nullable|string|max:255',
            'contact_phone'   => 'nullable|string|max:50',
            'location'        => 'nullable|string|max:500',
            'scheduled_at'    => 'nullable|date',
        ];
    }
}
