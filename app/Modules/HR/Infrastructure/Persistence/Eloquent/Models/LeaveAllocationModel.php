<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class LeaveAllocationModel extends CoreModel
{


    protected $table = 'leave_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'leave_type_id' => 'integer',
            'year' => 'integer',
            'allocated_days' => 'decimal:4',
            'used_days' => 'decimal:4',
            'pending_days' => 'decimal:4',
            'carried_forward' => 'decimal:4',
            'expiry_date' => 'date',
            'created_by' => 'integer',
        ]);
    }
}