<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class AuthSessionModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_sessions';

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
