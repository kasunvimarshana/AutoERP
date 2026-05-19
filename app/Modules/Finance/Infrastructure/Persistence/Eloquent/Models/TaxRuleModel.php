<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRuleModel extends Model
{
    protected $table = 'tax_rules';
    protected $guarded = [];
}
