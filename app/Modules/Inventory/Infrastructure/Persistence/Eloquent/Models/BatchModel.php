<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BatchModel extends Model
{
    protected $table = 'batches';
    protected $guarded = [];
}
