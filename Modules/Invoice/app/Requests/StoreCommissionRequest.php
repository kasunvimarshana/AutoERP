<?php

declare(strict_types=1);

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Invoice\Enums\CommissionStatus;

/**
 * Store Commission Request
 *
 * Validates data for creating a commission
 */
class StoreCommissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:'.implode(',', CommissionStatus::values())],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invoice_id' => 'invoice',
            'driver_id' => 'driver',
            'commission_rate' => 'commission rate',
            'commission_amount' => 'commission amount',
        ];
    }
}
