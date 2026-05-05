<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRentalReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'expected_return_at' => 'nullable|date_format:Y-m-d H:i:s',
            'base_rate' => 'nullable|numeric|decimal:0,6',
            'estimated_amount' => 'nullable|numeric|decimal:0,6',
            'notes' => 'nullable|string',
        ];
    }
}
