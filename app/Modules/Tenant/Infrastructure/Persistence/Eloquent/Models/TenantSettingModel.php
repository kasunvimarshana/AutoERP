<?php

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSettingModel extends Model
{
    protected $table = 'tenant_settings';
    protected $guarded = [];
}
