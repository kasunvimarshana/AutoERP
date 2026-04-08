<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class JournalEntryLineModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'journal_entry_lines';
    public $timestamps = true;
    protected $fillable = [
        'tenant_id','journal_entry_id','account_id','type',
        'amount','currency_code','description','sequence',
    ];
    protected $casts = [
        'amount'   => 'float',
        'sequence' => 'integer',
    ];
}
