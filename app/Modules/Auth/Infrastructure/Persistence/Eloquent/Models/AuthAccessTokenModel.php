<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class AuthAccessTokenModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_access_tokens';

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
