<?php

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGroupModel extends Model
{
    protected $table = 'paymentGroups';
    protected $guarded = [];
}
