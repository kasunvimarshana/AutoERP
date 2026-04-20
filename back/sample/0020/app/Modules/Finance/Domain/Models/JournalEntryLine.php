<?php

namespace App\Modules\Finance\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model {
    protected $fillable = ['journal_entry_id', 'account_id', 'debit', 'credit', 'memo'];

    public function account() {
        return $this->belongsTo(Account::class);
    }
}
