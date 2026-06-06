<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class AuthVerificationChallengeModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_verification_challenges';

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
