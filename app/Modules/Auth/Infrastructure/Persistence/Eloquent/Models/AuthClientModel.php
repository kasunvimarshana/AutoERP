<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class AuthClientModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_clients';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'allowed_grant_types' => 'array',
            'allowed_scopes' => 'array',
            'redirect_uris' => 'array',
            'trusted_origins' => 'array',
            'is_confidential' => 'boolean',
            'is_first_party' => 'boolean',
            'secret_rotated_at' => 'datetime',
            'expires_at' => 'datetime',
        ]);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AuthProviderModel::class, 'provider_id');
    }
}
