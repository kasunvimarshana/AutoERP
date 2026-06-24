<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthRegistrationInvitationModel extends TenantOwnedModel
{
    protected $table = 'auth_registration_invitations';

    protected $fillable = [
        'tenant_id',
        'organization_unit_id',
        'role_id',
        'email',
        'token_hash',
        'purpose',
        'status',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
        'revoked_at',
        'metadata',
        'created_by',
        'updated_by',
        'row_version',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'role_id' => 'integer',
            'accepted_by_user_id' => 'integer',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }
}
