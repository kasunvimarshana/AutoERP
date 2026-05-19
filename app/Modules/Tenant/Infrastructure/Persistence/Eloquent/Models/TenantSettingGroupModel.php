<?php

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSettingGroupModel extends Model
{
    protected $table = 'tenant_setting_groups';
    protected $guarded = [];
}
