<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class EmployeeUserAccountModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'employee_user_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'user_id' => 'integer',
            'is_primary' => 'boolean',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'linked_user_by' => 'integer',
            'invited_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
