<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'      => 'required|integer',
            'employee_id'    => 'required|integer',
            'date'           => 'required|date_format:Y-m-d',
            'check_in_time'  => 'required|date_format:H:i:s',
            'status'         => 'required|string|in:present,absent,late,half_day',
            'notes'          => 'nullable|string',
            'hours_worked'   => 'nullable|numeric|min:0',
            'check_out_time' => 'nullable|date_format:H:i:s',
        ];
    }
}
