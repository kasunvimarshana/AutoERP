<?php

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class JournalLineModel extends Model
{
    protected $table = 'accounting_journal_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }
}
