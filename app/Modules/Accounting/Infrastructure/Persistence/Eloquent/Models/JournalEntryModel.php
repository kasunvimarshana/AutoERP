<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class JournalEntryModel extends BaseModel {
    protected $table = 'journal_entries';
    protected $fillable = ['tenant_id','entry_number','status','description','currency','total_debit','total_credit','reference','created_by','posted_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','total_debit'=>'float','total_credit'=>'float',
        'posted_at'=>'datetime','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
    public function lines() { return $this->hasMany(JournalEntryLineModel::class,'journal_entry_id'); }
}
