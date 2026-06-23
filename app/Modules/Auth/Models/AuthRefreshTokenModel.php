<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\Concerns\HasValidTokenScope;
use Modules\Core\Models\CoreModel;

final class AuthRefreshTokenModel extends CoreModel
{
    use HasValidTokenScope;
    use SoftDeletes;

    protected $table = 'auth_refresh_tokens';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'access_token_id',
        'provider_id',
        'client_id',
        'identity_id',
        'session_id',
        'user_id',
        'refresh_key',
        'refresh_hash',
        'rotated',
        'rotated_at',
        'replaced_by_expires_at',
        'token_scope',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'rotated' => 'boolean',
            'issued_at' => 'datetime',
            'rotated_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'replaced_by_expires_at' => 'datetime',
        ]);
    }

    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AuthAccessTokenModel::class, 'access_token_id');
    }
}
