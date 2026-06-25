<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\User\Models\UserModel;

final class AuthPlatformSessionModel extends CoreModel
{
    protected $table = 'auth_platform_sessions';

    protected $fillable = [
        'public_id', 'user_id', 'status', 'ip_address', 'user_agent', 'device_name',
        'authenticated_at', 'last_activity_at', 'expires_at', 'revoked_at', 'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'user_id' => 'integer',
            'row_version' => 'integer',
            'authenticated_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
