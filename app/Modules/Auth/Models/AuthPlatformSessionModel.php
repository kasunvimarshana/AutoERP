<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\User\Models\PlatformOperatorModel;

final class AuthPlatformSessionModel extends CoreModel
{
    protected $table = 'auth_platform_sessions';
    protected $fillable = [
        'public_id', 'platform_operator_id', 'status', 'ip_address', 'user_agent',
        'device_name', 'authenticated_at', 'last_activity_at', 'expires_at',
        'revoked_at', 'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer',
            'row_version' => 'integer',
            'authenticated_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(PlatformOperatorModel::class, 'platform_operator_id');
    }
}
