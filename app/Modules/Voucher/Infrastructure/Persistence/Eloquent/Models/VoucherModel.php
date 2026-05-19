<?php

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherModel extends Model
{
    protected $table = 'vouchers';
    protected $guarded = [];
}
