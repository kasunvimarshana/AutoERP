<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Models;
use Illuminate\Database\Eloquent\Model;
class JournalLine extends Model {
    protected $table = 'journal_lines';
    protected $fillable = [
        'tenant_id','journal_entry_id','account_id',
        'description','debit_amount','credit_amount',
    ];
    protected $casts = [
        'debit_amount'  => 'decimal:4',
        'credit_amount' => 'decimal:4',
    ];
    public $timestamps = false;
    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
