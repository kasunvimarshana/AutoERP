<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ApTransactionModel extends Model
{
    protected $table = 'ap_transactions';
    protected $guarded = [];
}
