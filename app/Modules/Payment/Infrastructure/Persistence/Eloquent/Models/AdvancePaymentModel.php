<?php

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class AdvancePaymentModel extends Model
{
    protected $table = 'advance_payments';
    protected $guarded = [];
}
