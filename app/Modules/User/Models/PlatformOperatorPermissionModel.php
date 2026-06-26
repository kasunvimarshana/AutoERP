<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class PlatformOperatorPermissionModel extends CoreModel
{
    protected $table = 'platform_operator_permissions';
    protected $fillable = ['platform_operator_id', 'platform_permission_id', 'granted_by_operator_id'];

    public function permission(): BelongsTo { return $this->belongsTo(PlatformPermissionModel::class, 'platform_permission_id'); }
    public function operator(): BelongsTo { return $this->belongsTo(PlatformOperatorModel::class, 'platform_operator_id'); }
    public function grantedBy(): BelongsTo { return $this->belongsTo(PlatformOperatorModel::class, 'granted_by_operator_id'); }
}
