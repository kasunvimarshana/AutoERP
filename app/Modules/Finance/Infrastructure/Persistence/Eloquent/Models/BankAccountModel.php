<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccountModel extends Model
{
    protected $table = 'bank_accounts';
    protected $guarded = [];
}
