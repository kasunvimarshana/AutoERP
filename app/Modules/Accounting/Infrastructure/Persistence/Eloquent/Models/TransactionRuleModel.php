<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class TransactionRuleModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'transaction_rules';
    public $timestamps = true;
    protected $fillable = [
        'tenant_id','name','priority','conditions','apply_to',
        'account_id','description','is_active',
    ];
    protected $casts = [
        'conditions' => 'array',
        'priority'   => 'integer',
        'is_active'  => 'boolean',
    ];
}
