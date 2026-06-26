<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class UserRoleModel extends TenantOwnedModel
{
    protected $table = 'user_roles';
    protected $fillable = ['tenant_id', 'row_version', 'user_id', 'role_id', 'created_by_user_id', 'updated_by_user_id'];

    public function user(): BelongsTo { return $this->belongsTo(UserModel::class, 'user_id'); }
    public function role(): BelongsTo { return $this->belongsTo(RoleModel::class, 'role_id'); }
}
