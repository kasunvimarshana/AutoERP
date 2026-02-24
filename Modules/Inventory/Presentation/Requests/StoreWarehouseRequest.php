<?php
namespace Modules\Inventory\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|array',
            'responsible_user_id' => 'nullable|uuid',
            'is_active' => 'nullable|boolean',
        ];
    }
}
