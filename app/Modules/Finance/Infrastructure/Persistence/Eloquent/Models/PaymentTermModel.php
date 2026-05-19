<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTermModel extends Model
{
    protected $table = 'payment_terms';
    protected $guarded = [];
}
