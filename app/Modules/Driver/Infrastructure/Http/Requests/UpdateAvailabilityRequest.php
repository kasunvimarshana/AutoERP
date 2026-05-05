<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'available_from' => 'nullable|date_format:Y-m-d H:i:s',
            'available_to' => 'nullable|date_format:Y-m-d H:i:s',
            'days_of_week' => 'nullable|string',
        ];
    }
}
