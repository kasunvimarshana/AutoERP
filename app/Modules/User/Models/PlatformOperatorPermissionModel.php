<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class PlatformOperatorPermissionModel extends CoreModel
{
    protected $table = 'platform_operator_permissions';
    protected $fillable = ['user_id', 'platform_permission_id', 'granted_by'];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(PlatformPermissionModel::class, 'platform_permission_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
