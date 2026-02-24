<?php
namespace Modules\Sales\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Sales\Domain\Enums\CustomerType;
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $types = implode(',', array_column(CustomerType::cases(), 'value'));
        return [
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:'.$types,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'price_list_id' => 'nullable|uuid',
            'payment_terms' => 'nullable|string|max:100',
            'currency' => 'nullable|string|size:3',
            'tax_id' => 'nullable|string|max:50',
            'billing_address' => 'nullable|array',
        ];
    }
}
