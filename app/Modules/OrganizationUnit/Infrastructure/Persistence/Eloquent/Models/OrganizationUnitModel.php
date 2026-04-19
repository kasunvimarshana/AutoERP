<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class OrganizationUnitModel extends Model
{
    use HasAudit;
    use SoftDeletes;

    protected $table = 'org_units';

    protected $fillable = [
        'tenant_id',
        'type_id',
        'parent_id',
        'manager_user_id',
        'name',
        'code',
        'path',
        'depth',
        'metadata',
        'is_active',
        'description',
        '_lft',
        '_rgt',
    ];

    protected $casts = [
        'metadata' => 'array',
        'depth' => 'integer',
        'is_active' => 'boolean',
        '_lft' => 'integer',
        '_rgt' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitTypeModel::class, 'type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrganizationUnitAttachmentModel::class, 'org_unit_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'manager_user_id');
    }

    public function organizationUnitUsers(): HasMany
    {
        return $this->hasMany(OrganizationUnitUserModel::class, 'org_unit_id');
    }
}
