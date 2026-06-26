<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\User\Models\PlatformOperatorModel;

final class AuthPlatformMfaMethodModel extends CoreModel
{
    protected $table = 'auth_platform_mfa_methods';
    protected $fillable = [
        'platform_operator_id', 'secret', 'backup_code_hashes', 'status',
        'confirmed_at', 'last_used_at', 'row_version',
    ];
    protected $hidden = ['secret', 'backup_code_hashes'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer',
            'secret' => 'encrypted',
            'backup_code_hashes' => 'encrypted:array',
            'confirmed_at' => 'datetime',
            'last_used_at' => 'datetime',
        ]);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(PlatformOperatorModel::class, 'platform_operator_id');
    }
}
