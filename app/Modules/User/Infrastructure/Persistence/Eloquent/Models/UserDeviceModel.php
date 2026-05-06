<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

/**
 * @property int $id
 * @property int $user_id
 * @property string $device_token
 * @property string|null $platform
 * @property string|null $device_name
 * @property Carbon|null $last_active_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserDeviceModel extends BaseModel
{
    protected $table = 'user_devices';

    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
        'device_name',
        'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
