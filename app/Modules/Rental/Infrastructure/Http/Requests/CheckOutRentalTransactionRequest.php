<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRentalTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'agreement_id' => 'required|string|uuid',
            'vehicle_id' => 'required|string|uuid',
            'odometer_start' => 'required|integer|min:0',
            'fuel_level_start' => 'required|numeric|decimal:0,2',
            'check_out_at' => 'nullable|date_format:Y-m-d H:i:s',
            'checked_out_by' => 'nullable|string|uuid',
        ];
    }
}
