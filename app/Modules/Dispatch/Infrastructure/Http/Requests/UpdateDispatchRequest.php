<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_reference'     => 'nullable|string|max:100',
            'estimated_delivery_date'=> 'nullable|date',
            'carrier'                => 'nullable|string|max:100',
            'tracking_number'        => 'nullable|string|max:100',
            'notes'                  => 'nullable|string',
            'metadata'               => 'nullable|array',
            'total_weight'           => 'nullable|numeric|min:0',
        ];
    }
}
