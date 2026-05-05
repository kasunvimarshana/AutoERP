<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PayrollItemModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_payroll_items';

    protected $fillable = ['tenant_id', 'name', 'code', 'type', 'calculation_type', 'value', 'is_active', 'is_taxable', 'account_id', 'metadata'];

    protected $casts = ['tenant_id' => 'integer', 'account_id' => 'integer', 'is_active' => 'boolean', 'is_taxable' => 'boolean', 'metadata' => 'array'];
}
