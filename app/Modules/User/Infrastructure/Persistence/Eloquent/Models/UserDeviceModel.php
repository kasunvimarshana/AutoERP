<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UserDeviceModel extends Model
{
    protected $table = 'user_devices';
    protected $guarded = [];
}
