<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PerformanceCycleModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_performance_cycles';

    protected $fillable = ['tenant_id', 'org_unit_id', 'row_version', 'name', 'period_start', 'period_end', 'is_active', 'metadata'];

    protected $casts = ['tenant_id' => 'integer', 'org_unit_id' => 'integer', 'row_version' => 'integer', 'period_start' => 'date', 'period_end' => 'date', 'is_active' => 'boolean', 'metadata' => 'array'];
}
