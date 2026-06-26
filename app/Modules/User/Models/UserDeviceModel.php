<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class UserDeviceModel extends TenantOwnedModel
{
    protected $table = 'user_devices';
    protected $fillable = [
        'tenant_id', 'row_version', 'user_id',
        'device_token_hash', 'device_token_encrypted', 'platform', 'device_name',
        'last_active_at', 'revoked_at', 'registered_by_user_id', 'revoked_by_user_id',
    ];
    protected $hidden = ['device_token_hash', 'device_token_encrypted'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'device_token_encrypted' => 'encrypted',
            'last_active_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }

    public function user(): BelongsTo { return $this->belongsTo(UserModel::class, 'user_id'); }
}
