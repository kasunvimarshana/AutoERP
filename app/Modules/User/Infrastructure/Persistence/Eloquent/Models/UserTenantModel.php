<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UserTenantModel extends Model
{
    protected $table = 'user_tenants';
    protected $guarded = [];
}
