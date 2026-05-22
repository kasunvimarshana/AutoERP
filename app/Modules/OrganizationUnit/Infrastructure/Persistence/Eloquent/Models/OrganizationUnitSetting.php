<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Concerns\HasTenant;

class OrganizationUnitSetting extends Model
{
    use HasTenant;

    protected $table = 'organization_unit_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'organization_unit_id'
        );
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnitSettingGroup',
            'group_id'
        );
    }
}
