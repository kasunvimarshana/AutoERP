<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class UserOrganizationUnitModel extends CoreModel
{
    use HasImmutableTenantOwnership;

    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            $isDefault = (bool) $model->getAttribute('is_default');
            $defaultMarker = $model->getAttribute('default_marker');
            $status = (string) $model->getAttribute('status');

            if ($isDefault && $status !== 'active') {
                throw new \LogicException('Only an active organization-unit assignment can be the default.');
            }

            if ($defaultMarker !== null && $defaultMarker !== 'default') {
                throw new \LogicException('Organization-unit default marker is invalid.');
            }

            if ($isDefault !== ($defaultMarker === 'default')) {
                throw new \LogicException('Organization-unit default flag and marker must remain consistent.');
            }
        });
    }

    protected $table = 'user_organization_units';

    protected $fillable = [
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'user_id',
        'status',
        'is_default',
        'default_marker',
        'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'user_id' => 'integer',
            'is_default' => 'boolean',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
