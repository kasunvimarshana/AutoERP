<?php
namespace Modules\Inventory\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Domain\Enums\ProductType;
use Modules\Inventory\Domain\Enums\ProductStatus;
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $types = implode(',', array_column(ProductType::cases(), 'value'));
        $statuses = implode(',', array_column(ProductStatus::cases(), 'value'));
        return [
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:'.$types,
            'sku' => 'required|string|max:100',
            'category_id' => 'nullable|uuid',
            'unit_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'purchase_uom' => 'nullable|string|max:50',
            'sale_uom' => 'nullable|string|max:50',
            'inventory_uom' => 'nullable|string|max:50',
            'status' => 'nullable|in:'.$statuses,
            'barcode_ean13' => 'nullable|string|max:13',
            'track_lots' => 'nullable|boolean',
            'track_serials' => 'nullable|boolean',
            'description' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'reorder_point' => 'nullable|numeric|min:0',
        ];
    }
}
