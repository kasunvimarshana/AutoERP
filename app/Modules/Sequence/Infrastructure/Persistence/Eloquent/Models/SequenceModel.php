<?php

namespace Modules\Sequence\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class SequenceModel extends Model
{
    protected $table = 'sequences';
    protected $guarded = [];
}
