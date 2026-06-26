<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthProviderModel extends TenantOwnedModel
{
    protected $table = 'auth_providers';
    protected $fillable = ['tenant_id', 'provider_key', 'name', 'driver', 'status', 'row_version'];
}
