<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class AccountModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'status',
        'description',
        'is_system_account',
        'opening_balance',
        'current_balance',
    ];

    protected $casts = [
        'opening_balance' => 'string',
        'current_balance' => 'string',
        'is_system_account' => 'boolean',
    ];
}
