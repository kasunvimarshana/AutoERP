<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthIdentityModel extends TenantOwnedModel
{
    protected $table = 'auth_identities';
    protected $fillable = [
        'tenant_id', 'provider_id', 'user_id', 'provider_user_key', 'status',
        'primary_marker', 'verified_at', 'last_authenticated_at', 'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'verified_at' => 'immutable_datetime', 'last_authenticated_at' => 'immutable_datetime',
        ]);
    }
}
