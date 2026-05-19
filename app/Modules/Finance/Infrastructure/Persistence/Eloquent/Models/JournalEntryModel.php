<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryModel extends Model
{
    protected $table = 'journal_entries';
    protected $guarded = [];
}
