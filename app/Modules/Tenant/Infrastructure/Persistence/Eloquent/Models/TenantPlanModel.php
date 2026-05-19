<?php

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPlanModel extends Model
{
    protected $table = 'tenant_plans';
    protected $guarded = [];
}
