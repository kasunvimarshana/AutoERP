<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class BankAccountModel extends BaseModel
{
    protected $table = 'bank_accounts';
    protected $fillable = [
        'tenant_id', 'account_id', 'name', 'bank_name', 'account_number',
        'account_type', 'currency', 'current_balance', 'is_active',
        'description', 'last_synced_at',
    ];
    protected $casts = [
        'id'              => 'int',
        'tenant_id'       => 'int',
        'account_id'      => 'int',
        'current_balance' => 'float',
        'is_active'       => 'bool',
        'last_synced_at'  => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];
}
