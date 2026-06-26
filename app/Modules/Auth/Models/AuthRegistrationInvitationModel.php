<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Tenant\Models\TenantModel;

final class AuthRegistrationInvitationModel extends TenantOwnedModel
{
    protected $table = 'auth_registration_invitations';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'user_id',
        'organization_unit_id',
        'role_id',
        'email',
        'token_hash',
        'delivery_token',
        'purpose',
        'status',
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
            'user_id' => 'integer',
            'organization_unit_id' => 'integer',
            'role_id' => 'integer',
            'accepted_by_user_id' => 'integer',
            'delivery_token' => 'encrypted',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AuthRegistrationInvitationDeliveryModel::class, 'invitation_id');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(AuthRegistrationInvitationDeliveryModel::class, 'invitation_id')
            ->latestOfMany('attempt_number');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }
}
