<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

/**
 * Permissions are system-managed records without soft-delete support.
 * They extend Model directly (not BaseModel) to avoid inheriting SoftDeletes.
 */
class PermissionModel extends Model
{
    use HasAudit;

    protected $table = 'permissions';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'module',
        'guard_name',
        'metadata',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'id'         => 'int',
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'permission_role', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}
