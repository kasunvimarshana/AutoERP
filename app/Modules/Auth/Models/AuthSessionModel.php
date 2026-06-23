<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\CoreModel;
use Modules\User\Models\UserModel;

final class AuthSessionModel extends CoreModel
{
    use HasImmutableTenantOwnership;
    use SoftDeletes;

    protected $table = 'auth_sessions';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'provider_id',
        'identity_id',
        'user_id',
        'session_key',
        'status',
        'ip_address',
        'user_agent',
        'device_name',
        'last_activity_at',
        'revoked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AuthProviderModel::class, 'provider_id');
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(AuthIdentityModel::class, 'identity_id');
    }
}
