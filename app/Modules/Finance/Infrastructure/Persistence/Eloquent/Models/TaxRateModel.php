<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRateModel extends Model
{
    protected $table = 'tax_rates';
    protected $guarded = [];
}
