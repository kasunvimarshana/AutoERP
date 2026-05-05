<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRentalReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'vehicle_id' => 'required|string|uuid',
            'customer_id' => 'required|string|uuid',
            'driver_id' => 'nullable|string|uuid',
            'start_at' => 'required|date_format:Y-m-d H:i:s',
            'expected_return_at' => 'required|date_format:Y-m-d H:i:s|after:start_at',
            'billing_unit' => 'nullable|string|in:hour,day,week,month',
            'base_rate' => 'required|numeric|decimal:0,6',
            'estimated_distance' => 'nullable|integer|min:0',
            'estimated_amount' => 'required|numeric|decimal:0,6',
            'notes' => 'nullable|string',
        ];
    }
}
