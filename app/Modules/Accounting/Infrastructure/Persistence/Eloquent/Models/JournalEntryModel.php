<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;
class JournalEntryModel extends BaseModel
{
    use HasUuid, HasTenant;
    protected $table = 'journal_entries';
    protected $fillable = [
        'tenant_id','number','date','description','reference',
        'status','source_type','source_id','posted_at','voided_at',
    ];
    protected $casts = [
        'date'      => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];
}
