<?php

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class AdvancePaymentAllocationModel extends Model
{
    protected $table = 'advance_payment_allocations';
    protected $guarded = [];
}
