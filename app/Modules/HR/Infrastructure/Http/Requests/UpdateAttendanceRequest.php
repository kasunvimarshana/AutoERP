<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'           => 'sometimes|required|date_format:Y-m-d',
            'check_in_time'  => 'sometimes|required|date_format:H:i:s',
            'status'         => 'sometimes|required|string|in:present,absent,late,half_day',
            'notes'          => 'nullable|string',
            'hours_worked'   => 'nullable|numeric|min:0',
            'check_out_time' => 'nullable|date_format:H:i:s',
        ];
    }
}
