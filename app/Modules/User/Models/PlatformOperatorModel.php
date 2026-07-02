<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final class PlatformOperatorModel extends Authenticatable
{
    use Notifiable;

    protected $table = 'platform_operators';

    protected $fillable = [
        'row_version', 'first_name', 'last_name', 'email', 'status',
        'credentials_ready_at', 'activated_at', 'deactivated_at',
        'created_by_operator_id', 'updated_by_operator_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $operator): void {
            if (! app()->bound(TenantExecutionContextInterface::class)
                || ! app(TenantExecutionContextInterface::class)->isControlPlane()
                || app(TenantExecutionContextInterface::class)->tenantId() !== null
            ) {
                throw new LogicException('Platform operators can only be written in the platform control plane.');
            }
            $operator->setAttribute('email', strtolower(trim((string) $operator->getAttribute('email'))));
        });
    }

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'credentials_ready_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function permissionAssignments(): HasMany
    {
        return $this->hasMany(PlatformOperatorPermissionModel::class, 'platform_operator_id');
    }

}
