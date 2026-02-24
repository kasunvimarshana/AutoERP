<?php
namespace Modules\Purchase\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'vendor_id' => 'required|uuid',
            'currency' => 'nullable|string|size:3',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'nullable|uuid',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.qty' => 'required|numeric|min:0.00000001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.uom' => 'nullable|string|max:50',
        ];
    }
}
