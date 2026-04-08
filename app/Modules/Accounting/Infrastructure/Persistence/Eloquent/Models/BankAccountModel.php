<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class BankAccountModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'bank_accounts';
    protected $fillable = [
        'tenant_id','account_id','name','account_type','bank_name',
        'account_number','routing_number','currency_code',
        'current_balance','last_reconciled_at','is_active',
    ];
    protected $casts = [
        'current_balance'    => 'float',
        'is_active'          => 'boolean',
        'last_reconciled_at' => 'datetime',
    ];
}
