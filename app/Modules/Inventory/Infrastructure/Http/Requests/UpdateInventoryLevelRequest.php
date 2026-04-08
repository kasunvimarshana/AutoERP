<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryLevelRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'qty_on_hand'  => 'sometimes|numeric',
            'qty_reserved' => 'sometimes|numeric|min:0',
            'qty_on_order' => 'sometimes|numeric|min:0',
            'reorder_point'=> 'sometimes|nullable|numeric|min:0',
            'reorder_qty'  => 'sometimes|nullable|numeric|min:0',
            'max_qty'      => 'sometimes|nullable|numeric|min:0',
            'min_qty'      => 'sometimes|nullable|numeric|min:0',
        ];
    }
}
