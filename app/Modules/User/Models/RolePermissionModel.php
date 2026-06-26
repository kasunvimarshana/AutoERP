<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class RolePermissionModel extends TenantOwnedModel
{
    protected $table = 'role_permissions';
    protected $fillable = ['tenant_id', 'row_version', 'role_id', 'permission_id', 'created_by_user_id', 'updated_by_user_id'];

    public function role(): BelongsTo { return $this->belongsTo(RoleModel::class, 'role_id'); }
    public function permission(): BelongsTo { return $this->belongsTo(PermissionModel::class, 'permission_id'); }
}
