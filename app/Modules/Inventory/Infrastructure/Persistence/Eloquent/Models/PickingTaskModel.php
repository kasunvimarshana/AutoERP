<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PickingTaskModel extends Model
{
    protected $table = 'picking_tasks';
    protected $guarded = [];
}
