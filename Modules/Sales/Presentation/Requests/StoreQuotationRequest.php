<?php
namespace Modules\Sales\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'customer_id' => 'required|uuid',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'lines' => 'required|array|min:1',
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
