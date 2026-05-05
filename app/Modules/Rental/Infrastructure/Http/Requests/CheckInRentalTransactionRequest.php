<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRentalTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'odometer_end' => 'required|integer|min:0',
            'fuel_level_end' => 'required|numeric|decimal:0,2',
            'checked_in_by' => 'nullable|string|uuid',
        ];
    }
}
