<?php

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocationModel extends Model
{
    protected $table = 'payment_allocations';
    protected $guarded = [];
}
