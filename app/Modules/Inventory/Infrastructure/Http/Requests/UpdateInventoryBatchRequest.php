<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lot_number'        => 'sometimes|nullable|string|max:100',
            'manufacture_date'  => 'sometimes|nullable|date',
            'expiry_date'       => 'sometimes|nullable|date',
            'best_before_date'  => 'sometimes|nullable|date',
            'supplier_batch_ref'=> 'sometimes|nullable|string|max:255',
            'unit_cost'         => 'sometimes|numeric|min:0',
            'currency'          => 'sometimes|string|size:3',
            'status'            => 'sometimes|string|in:active,quarantine,expired,depleted,recalled',
            'notes'             => 'sometimes|nullable|string',
            'metadata'          => 'nullable|array',
        ];
    }
}
