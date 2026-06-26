<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

final class AuthPlatformMfaMethodModel extends CoreModel
{
    protected $table = 'auth_platform_mfa_methods';
    protected $fillable = [
        'platform_operator_id', 'secret', 'backup_code_hashes', 'status',
        'enrollment_proof_digest', 'enrollment_proof_expires_at', 'last_totp_counter',
        'confirmed_at', 'last_used_at', 'disabled_at', 'row_version',
    ];
    protected $hidden = ['secret', 'backup_code_hashes', 'enrollment_proof_digest'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer', 'secret' => 'encrypted',
            'backup_code_hashes' => 'encrypted:array', 'last_totp_counter' => 'integer',
            'enrollment_proof_expires_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime', 'last_used_at' => 'immutable_datetime',
            'disabled_at' => 'immutable_datetime',
        ]);
    }
}
