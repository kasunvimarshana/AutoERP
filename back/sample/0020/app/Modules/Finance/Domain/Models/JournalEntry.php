<?php

namespace App\Modules\Finance\Domain\Models;

use App\Modules\Core\Domain\Models\BaseModel;

class JournalEntry extends BaseModel {
    protected $fillable = ['tenant_id', 'posting_date', 'reference_no', 'description', 'created_by'];

    public function lines() {
        return $this->hasMany(JournalEntryLine::class);
    }
}
