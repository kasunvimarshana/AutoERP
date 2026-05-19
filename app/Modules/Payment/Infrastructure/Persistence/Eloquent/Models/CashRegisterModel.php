<?php

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterModel extends Model
{
    protected $table = 'cash_registers';
    protected $guarded = [];
}
