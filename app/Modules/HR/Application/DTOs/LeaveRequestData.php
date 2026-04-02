<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class LeaveRequestData extends BaseDto
{
    public int $tenant_id;

    public int $employee_id;

    public string $leave_type;

    public string $start_date;

    public string $end_date;

    public ?string $reason = null;

    public string $status = 'pending';

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer',
            'employee_id' => 'required|integer',
            'leave_type'  => 'required|string|in:annual,sick,personal,maternity,paternity,unpaid,other',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'reason'      => 'nullable|string',
            'status'      => 'required|string|in:pending,approved,rejected,cancelled',
            'metadata'    => 'nullable|array',
        ];
    }
}
