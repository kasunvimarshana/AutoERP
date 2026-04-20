<?php

namespace App\Modules\Finance\Domain\Models;

use App\Modules\Core\Domain\Models\BaseModel;

class Account extends BaseModel {
    protected $fillable = ['tenant_id', 'parent_id', 'code', 'name', 'type', 'is_active'];

    public function parent() {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Account::class, 'parent_id');
    }
}
