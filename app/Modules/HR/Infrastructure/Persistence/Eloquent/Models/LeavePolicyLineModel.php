<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class LeavePolicyLineModel extends CoreModel
{


    protected $table = 'leave_policy_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'leave_policy_id' => 'integer',
            'leave_type_id' => 'integer',
            'annual_allocation' => 'decimal:4',
            'accrual_amount' => 'decimal:4',
            'carry_forward_max' => 'decimal:4',
        ]);
    }
}