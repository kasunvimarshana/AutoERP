<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class AuthProviderModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_providers';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'config' => 'array',
            'is_sso' => 'boolean',
            'last_synced_at' => 'datetime',
        ]);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(AuthClientModel::class, 'provider_id');
    }
}
