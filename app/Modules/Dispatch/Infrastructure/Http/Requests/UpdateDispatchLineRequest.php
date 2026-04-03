<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'description'          => 'nullable|string',
            'quantity'             => 'nullable|numeric|min:0',
            'warehouse_location_id'=> 'nullable|integer',
            'batch_number'         => 'nullable|string|max:100',
            'serial_number'        => 'nullable|string|max:100',
            'weight'               => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string',
            'metadata'             => 'nullable|array',
        ];
    }
}
