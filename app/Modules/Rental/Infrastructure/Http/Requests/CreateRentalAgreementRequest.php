<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRentalAgreementRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'reservation_id' => 'required|string|uuid',
            'signed_date' => 'nullable|date',
            'terms_and_conditions' => 'nullable|string',
            'total_price' => 'required|numeric|decimal:0,6',
            'deposit_required' => 'required|numeric|decimal:0,6',
            'insurance_required' => 'nullable|boolean',
            'additional_notes' => 'nullable|string',
        ];
    }
}
