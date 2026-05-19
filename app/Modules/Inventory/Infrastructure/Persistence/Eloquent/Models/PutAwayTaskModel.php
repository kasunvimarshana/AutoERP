<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PutAwayTaskModel extends Model
{
    protected $table = 'put_away_tasks';
    protected $guarded = [];
}
