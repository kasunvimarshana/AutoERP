<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class EmployeeStatusHistoryModel extends CoreModel
{
    protected $table = 'employee_status_histories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }
}
