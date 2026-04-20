<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BankCategoryRuleModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'bank_category_rules';

    protected $fillable = [
        'tenant_id',
        'bank_account_id',
        'name',
        'priority',
        'conditions',
        'account_id',
        'description_template',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];
}
