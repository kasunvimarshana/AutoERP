<?php

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDomainModel extends Model
{
    protected $table = 'tenant_domains';
    protected $guarded = [];
}
