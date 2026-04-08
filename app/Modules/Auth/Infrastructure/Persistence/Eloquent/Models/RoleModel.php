<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class RoleModel extends BaseModel
{
    protected $table = 'roles';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system',
        'guard_name',
        'metadata',
    ];

    protected $casts = [
        'id'        => 'int',
        'tenant_id' => 'int',
        'is_system' => 'boolean',
        'metadata'  => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionModel::class, 'permission_role', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class, 'role_user', 'role_id', 'user_id')
            ->withTimestamps()
            ->withPivot(['tenant_id', 'assigned_at', 'expires_at']);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }
}
