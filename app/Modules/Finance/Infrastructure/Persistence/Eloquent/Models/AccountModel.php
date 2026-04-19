<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class AccountModel extends BaseModel
{

    use HasTenant;
    use HasAudit;
    use SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'sub_type',
        'normal_balance',
        'is_system',
        'is_bank_account',
        'is_credit_card',
        'currency_id',
        'description',
        'is_active',
        'path',
        'depth',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_credit_card' => 'boolean',
        'is_active' => 'boolean',
        'depth' => 'integer',
    ];
}
