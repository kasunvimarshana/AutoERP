<?php

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TimezoneModel extends Model
{
    protected $table = 'timezones';
    protected $guarded = [];
}
