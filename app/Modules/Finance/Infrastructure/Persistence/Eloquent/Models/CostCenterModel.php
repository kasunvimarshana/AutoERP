<?php

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CostCenterModel extends Model
{
    protected $table = 'cost_centers';
    protected $guarded = [];
}
