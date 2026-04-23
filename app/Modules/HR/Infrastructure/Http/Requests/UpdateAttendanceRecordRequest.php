<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
            'break_duration' => 'nullable|integer|min:0',
            'worked_minutes' => 'nullable|integer|min:0',
            'overtime_minutes' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:present,absent,late,half_day,on_leave,holiday,weekend,work_from_home',
            'shift_id' => 'nullable|integer',
            'remarks' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
