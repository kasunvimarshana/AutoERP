<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SalaryComponentModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'salary_components';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'default_value' => 'decimal:4',
            'is_taxable' => 'boolean',
            'affects_net_pay' => 'boolean',
            'account_id' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
        ]);
    }
}