<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class JournalEntryLineModel extends Model {
    protected $table = 'journal_entry_lines';
    public $timestamps = false;
    protected $fillable = ['journal_entry_id','account_id','debit','credit','description'];
    protected $casts = ['id'=>'int','journal_entry_id'=>'int','account_id'=>'int','debit'=>'float','credit'=>'float'];
}
