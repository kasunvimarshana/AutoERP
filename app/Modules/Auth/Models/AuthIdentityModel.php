<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\User\Models\UserModel;

final class AuthIdentityModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_identities';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'claims' => 'array',
            'is_primary' => 'boolean',
            'last_authenticated_at' => 'datetime',
            'verified_at' => 'datetime',
        ]);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AuthProviderModel::class, 'provider_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
