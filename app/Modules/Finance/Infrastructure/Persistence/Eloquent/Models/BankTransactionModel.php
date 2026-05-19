<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransactionModel extends Model
{
    protected $table = 'bank_transactions';
    protected $guarded = [];
}
