<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class LeaveBalanceModel extends BaseModel
{
    use HasTenant;

    protected $table = 'hr_leave_balances';

    protected $fillable = ['tenant_id', 'employee_id', 'leave_type_id', 'year', 'allocated', 'used', 'pending', 'carried'];

    protected $casts = ['year' => 'integer', 'allocated' => 'float', 'used' => 'float', 'pending' => 'float', 'carried' => 'float'];
}
