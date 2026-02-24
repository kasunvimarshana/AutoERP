<?php

namespace Modules\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'uuid'],
            'lot_number'       => ['required', 'string', 'max:100'],
            'tracking_type'    => ['nullable', 'in:lot,serial'],
            'qty'              => ['required', 'numeric', 'min:0.00000001'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date'      => ['nullable', 'date', 'after_or_equal:manufacture_date'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ];
    }
}
