<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\CoreModel;

final class AuthClientModel extends CoreModel
{
    use HasImmutableTenantOwnership;
    use SoftDeletes;

    protected $table = 'auth_clients';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'provider_id',
        'client_key',
        'client_name',
        'client_secret_hash',
        'status',
        'allowed_grant_types',
        'allowed_scopes',
        'redirect_uris',
        'trusted_origins',
        'is_confidential',
        'is_first_party',
        'secret_rotated_at',
        'expires_at',
    ];

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
