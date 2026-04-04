<?php
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class JournalLineModel extends BaseModel
{
    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'currency',
        'description',
    ];

    protected $casts = [
        'debit'      => 'float',
        'credit'     => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }
}
