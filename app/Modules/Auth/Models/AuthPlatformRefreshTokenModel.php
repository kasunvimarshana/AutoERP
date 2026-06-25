<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class AuthPlatformRefreshTokenModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_platform_refresh_tokens';

    protected $fillable = [
        'row_version',
        'metadata',
        'access_token_id',
        'platform_session_id',
        'user_id',
        'refresh_key',
        'refresh_hash',
        'rotated',
        'rotated_at',
        'replaced_by_expires_at',
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
        return $this->belongsTo(AuthPlatformAccessTokenModel::class, 'access_token_id');
    }

    public function platformSession(): BelongsTo
    {
        return $this->belongsTo(AuthPlatformSessionModel::class, 'platform_session_id');
    }
}
