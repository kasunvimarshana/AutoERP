<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\PlatformOperatorModel;

final class AuthPlatformOperatorPasswordCredentialModel extends Model
{
    protected $table = 'auth_platform_operator_password_credentials';
    protected $fillable = [
        'platform_operator_id', 'password_hash', 'status', 'changed_at',
        'revoked_at', 'row_version',
    ];
    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'platform_operator_id' => 'integer',
            'row_version' => 'integer',
            'changed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(PlatformOperatorModel::class, 'platform_operator_id');
    }
}
