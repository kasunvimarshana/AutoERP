<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PayslipLineModel extends CoreModel
{


    protected $table = 'payslip_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'payslip_id' => 'integer',
            'salary_component_id' => 'integer',
            'amount' => 'decimal:4',
            'sequence' => 'integer',
        ]);
    }
}