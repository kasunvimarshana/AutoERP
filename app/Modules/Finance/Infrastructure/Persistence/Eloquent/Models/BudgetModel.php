<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetModel extends Model
{
    protected $table = 'budgets';
    protected $guarded = [];
}
