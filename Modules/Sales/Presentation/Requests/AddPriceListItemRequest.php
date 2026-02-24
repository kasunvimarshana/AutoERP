<?php
namespace Modules\Sales\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPriceListItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid',
            'variant_id' => 'nullable|uuid',
            'strategy'   => 'required|in:flat,percentage_discount',
            'amount'     => 'required|numeric|min:0.00000001',
            'min_qty'    => 'nullable|numeric|min:0.00000001',
            'uom'        => 'nullable|string|max:50',
        ];
    }
}
