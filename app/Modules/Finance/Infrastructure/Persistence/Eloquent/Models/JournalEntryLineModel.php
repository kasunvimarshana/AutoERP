<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLineModel extends Model
{
    protected $table = 'journal_entry_lines';
    protected $guarded = [];
}
