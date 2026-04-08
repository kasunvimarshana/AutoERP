<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryCycleCountLineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'expected_qty' => 'sometimes|numeric|min:0',
            'counted_qty'  => 'sometimes|numeric|min:0',
            'counted_by'   => 'nullable|integer',
            'location_id'  => 'sometimes|nullable|integer',
            'notes'        => 'sometimes|nullable|string',
        ];
    }
}
