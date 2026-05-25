<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class EmployeeContractModel extends CoreModel
{


    protected $table = 'employee_contracts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'salary' => 'decimal:4',
            'currency_id' => 'integer',
            'created_by' => 'integer',
        ]);
    }
}