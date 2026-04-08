<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class BankTransactionModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'bank_transactions';
    public $timestamps = true;
    protected $fillable = [
        'tenant_id','bank_account_id','date','description','amount',
        'type','status','source','account_id','journal_entry_id',
        'reference','metadata',
    ];
    protected $casts = [
        'date'     => 'date',
        'amount'   => 'float',
        'metadata' => 'array',
    ];
}
