<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class AuthVerificationChallengeModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_verification_challenges';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'provider_id',
        'identity_id',
        'user_id',
        'challenge_key',
        'challenge_type',
        'channel',
        'target',
        'challenge_hash',
        'attempts',
        'max_attempts',
        'status',
        'issued_at',
        'expires_at',
        'verified_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }
}
