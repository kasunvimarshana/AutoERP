<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLicenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'driver_id' => 'required|string|uuid',
            'license_number' => 'required|string|max:50|unique:license_models,license_number',
            'category' => 'nullable|string|max:10',
            'issued_date' => 'required|date',
            'expiry_date' => 'required|date|after:issued_date',
            'issuing_country' => 'nullable|string|max:2',
        ];
    }
}
