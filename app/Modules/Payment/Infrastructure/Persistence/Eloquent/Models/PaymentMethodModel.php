<?php

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodModel extends Model
{
    protected $table = 'payment_methods';
    protected $guarded = [];
}
