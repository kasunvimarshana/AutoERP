<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'driver_id' => 'required|string|uuid',
            'available_from' => 'required|date_format:Y-m-d H:i:s',
            'available_to' => 'required|date_format:Y-m-d H:i:s|after:available_from',
            'days_of_week' => 'nullable|string',
        ];
    }
}
