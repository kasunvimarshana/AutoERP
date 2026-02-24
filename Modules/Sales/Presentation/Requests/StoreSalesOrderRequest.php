<?php
namespace Modules\Sales\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'customer_id' => 'required|uuid',
            'quotation_id' => 'nullable|uuid',
            'currency' => 'nullable|string|size:3',
            'promised_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'sometimes|array|min:1',
            'lines.*.product_id' => 'nullable|uuid',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.qty' => 'required|numeric|min:0.00000001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.uom' => 'nullable|string|max:50',
        ];
    }
}
