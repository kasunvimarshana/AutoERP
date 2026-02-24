<?php
namespace Modules\Purchase\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ReceiveGoodsRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'received_at' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.purchase_order_line_id' => 'required|uuid',
            'lines.*.product_id' => 'nullable|uuid',
            'lines.*.qty_received' => 'required|numeric|min:0.00000001',
            'lines.*.qty_accepted' => 'required|numeric|min:0',
            'lines.*.qty_rejected' => 'nullable|numeric|min:0',
            'lines.*.rejection_reason' => 'nullable|string|max:255',
            'lines.*.lot_number' => 'nullable|string|max:100',
            'lines.*.expiry_date' => 'nullable|date',
            'lines.*.location_id' => 'nullable|uuid',
        ];
    }
}
