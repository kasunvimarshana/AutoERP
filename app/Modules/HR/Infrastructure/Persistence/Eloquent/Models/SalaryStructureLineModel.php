<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SalaryStructureLineModel extends CoreModel
{


    protected $table = 'salary_structure_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'salary_structure_id' => 'integer',
            'salary_component_id' => 'integer',
            'value' => 'decimal:4',
            'sequence' => 'integer',
        ]);
    }
}