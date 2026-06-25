<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;

final class AuthAccessTokenModel extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'auth_access_tokens';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'provider_id',
        'client_id',
        'identity_id',
        'session_id',
        'user_id',
        'token_key',
        'token_hash',
        'scopes',
        'grant_type',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'scopes' => 'array',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AuthSessionModel::class, 'session_id');
    }
}
