<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;

final class PlatformPermissionModel extends CoreModel
{
    protected $table = 'platform_permissions';
    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['is_active' => 'boolean']);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PlatformOperatorPermissionModel::class, 'platform_permission_id');
    }
}
