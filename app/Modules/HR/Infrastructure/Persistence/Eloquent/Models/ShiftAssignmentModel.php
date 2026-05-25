<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ShiftAssignmentModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'shift_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'shift_id' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'created_by' => 'integer',
        ]);
    }
}