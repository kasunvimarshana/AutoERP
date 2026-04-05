<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TransactionRuleModel extends BaseModel
{
    protected $table = 'transaction_rules';
    protected $fillable = [
        'tenant_id', 'name', 'is_active', 'priority', 'conditions',
        'actions', 'apply_to', 'match_count',
    ];
    protected $casts = [
        'id'          => 'int',
        'tenant_id'   => 'int',
        'is_active'   => 'bool',
        'priority'    => 'int',
        'conditions'  => 'array',
        'actions'     => 'array',
        'match_count' => 'int',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];
}
