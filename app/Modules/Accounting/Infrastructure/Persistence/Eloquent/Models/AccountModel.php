<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class AccountModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'accounts';
    protected $fillable = [
        'tenant_id','parent_id','code','name','type','sub_type',
        'normal_balance','currency_code','is_active','is_locked',
        'is_system_account','description','path','level',
    ];
    protected $casts = [
        'is_active'         => 'boolean',
        'is_locked'         => 'boolean',
        'is_system_account' => 'boolean',
        'level'             => 'integer',
    ];
}
