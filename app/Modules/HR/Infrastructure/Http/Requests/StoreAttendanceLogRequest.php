<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'biometric_device_id' => 'nullable|integer',
            'punch_time' => 'required|date',
            'punch_type' => 'required|string|in:in,out,break_start,break_end',
            'source' => 'nullable|string|max:50',
            'raw_data' => 'nullable|array',
        ];
    }
}
