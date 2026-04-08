<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class BudgetModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'budgets';
    protected $fillable = [
        'tenant_id','name','fiscal_year','start_date','end_date',
        'status','total_amount','notes',
    ];
    protected $casts = [
        'fiscal_year'  => 'integer',
        'start_date'   => 'date',
        'end_date'     => 'date',
        'total_amount' => 'float',
    ];
}
