<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class LeaveTypeModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'leave_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'max_days_per_year' => 'decimal:4',
            'carry_forward_max' => 'decimal:4',
            'allow_negative_balance' => 'boolean',
            'min_service_days' => 'integer',
            'created_by' => 'integer',
        ]);
    }
}