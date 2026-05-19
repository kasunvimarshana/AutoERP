<?php

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TenantModel extends Model
{
    protected $table = 'tenants';
    protected $guarded = [];
}
