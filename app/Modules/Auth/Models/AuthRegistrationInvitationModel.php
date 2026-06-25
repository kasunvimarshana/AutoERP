<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Tenant\Models\TenantModel;

final class AuthRegistrationInvitationModel extends TenantOwnedModel
{
    protected $table = 'auth_registration_invitations';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'organization_unit_id',
        'role_id',
        'email',
        'token_hash',
        'delivery_token',
        'purpose',
        'status',
        'delivery_status',
        'delivery_attempt_count',
        'delivery_requested_at',
        'delivered_at',
        'delivery_error_code',
        'delivery_error_message',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
        'revoked_at',
        'revocation_reason',
        'created_by',
        'updated_by',
        'row_version',
    ];

    protected $hidden = ['token_hash', 'delivery_token'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'role_id' => 'integer',
            'accepted_by_user_id' => 'integer',
            'delivery_attempt_count' => 'integer',
            'delivery_token' => 'encrypted',
            'delivery_requested_at' => 'datetime',
            'delivered_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }
}
