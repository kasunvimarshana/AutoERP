<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BankReconciliationModel extends Model
{
    protected $table = 'bank_reconciliations';
    protected $guarded = [];
}
