<?php
namespace Modules\Purchase\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Purchase\Domain\Enums\VendorStatus;
class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $statuses = implode(',', array_column(VendorStatus::cases(), 'value'));
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'currency' => 'nullable|string|size:3',
            'payment_terms' => 'nullable|string|max:100',
            'status' => 'nullable|in:'.$statuses,
            'rating' => 'nullable|numeric|min:0|max:5',
            'bank_details' => 'nullable|array',
            'address' => 'nullable|array',
        ];
    }
}
