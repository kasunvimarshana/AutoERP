<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class AuthPlatformAccessTokenModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_platform_access_tokens';

    protected $fillable = [
        'row_version',
        'metadata',
        'platform_session_id',
        'platform_operator_id',
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
            'platform_operator_id' => 'integer',
            'scopes' => 'array',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    public function platformSession(): BelongsTo
    {
        return $this->belongsTo(AuthPlatformSessionModel::class, 'platform_session_id');
    }
}
