<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthProviderModel extends TenantOwnedModel
{
    protected $table = 'auth_providers';
    protected $fillable = ['tenant_id', 'provider_key', 'name', 'driver', 'status', 'row_version'];
}
