<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ArTransactionModel extends Model
{
    protected $table = 'ar_transactions';
    protected $guarded = [];
}
