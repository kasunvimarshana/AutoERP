<?php

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class WriteOffsModel extends Model
{
    protected $table = 'write_offs';
    protected $guarded = [];
}
