<?php

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringVoucherModel extends Model
{
    protected $table = 'recurring_vouchers';
    protected $guarded = [];
}
