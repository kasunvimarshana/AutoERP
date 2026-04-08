<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class BudgetLineModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'budget_lines';
    public $timestamps = true;
    protected $fillable = [
        'tenant_id','budget_id','account_id','period','amounts','total_amount','notes',
    ];
    protected $casts = [
        'amounts'      => 'array',
        'total_amount' => 'float',
    ];
}
