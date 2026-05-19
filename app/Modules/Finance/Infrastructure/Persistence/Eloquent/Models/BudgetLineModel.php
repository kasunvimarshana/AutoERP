<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLineModel extends Model
{
    protected $table = 'budget_lines';
    protected $guarded = [];
}
