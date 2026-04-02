<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class AttendanceData extends BaseDto
{
    public int $tenant_id;

    public int $employee_id;

    public string $date;

    public string $check_in_time;

    public string $status;

    public ?string $notes = null;

    public ?float $hours_worked = null;

    public ?string $check_out_time = null;

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
