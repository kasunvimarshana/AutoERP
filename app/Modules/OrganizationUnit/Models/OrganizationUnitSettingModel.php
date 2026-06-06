<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Tenant\Models\TenantModel;

final class OrganizationUnitSettingModel extends CoreModel
{
    protected $table = 'organization_unit_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'group_id' => 'integer',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitSettingGroupModel::class, 'group_id');
    }
}
