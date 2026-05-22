<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class AccountModel extends Model
{
    protected $table = 'accounts';
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(AccountModel::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountModel::class, 'parent_id');
    }
}
