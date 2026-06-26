<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class UserPermissionModel extends TenantOwnedModel
{
    protected $table = 'user_permissions';
    protected $fillable = ['tenant_id', 'row_version', 'user_id', 'permission_id', 'created_by_user_id', 'updated_by_user_id'];

    public function user(): BelongsTo { return $this->belongsTo(UserModel::class, 'user_id'); }
    public function permission(): BelongsTo { return $this->belongsTo(PermissionModel::class, 'permission_id'); }
}
