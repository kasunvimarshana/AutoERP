<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class AuthRefreshTokenModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_refresh_tokens';

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
