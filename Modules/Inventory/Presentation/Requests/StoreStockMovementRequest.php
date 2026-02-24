<?php
namespace Modules\Inventory\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Domain\Enums\MovementType;
class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $types = implode(',', array_column(MovementType::cases(), 'value'));
        return [
            'type' => 'required|in:'.$types,
            'product_id' => 'required|uuid',
            'variant_id' => 'nullable|uuid',
            'from_location_id' => 'nullable|uuid',
            'to_location_id' => 'nullable|uuid',
            'qty' => 'required|numeric|min:0.00000001',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|uuid',
            'lot_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'location_id' => 'nullable|uuid',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
