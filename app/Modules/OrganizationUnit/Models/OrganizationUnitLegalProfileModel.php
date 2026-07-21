<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;

final class OrganizationUnitLegalProfileModel extends TenantOwnedModel
{
    protected $table = 'organization_unit_legal_profiles';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(static function (self $profile): void {
            if (! $profile->isDirty('row_version')) {
                $profile->row_version = ((int) $profile->getOriginal('row_version')) + 1;
            }
        });

        static::deleting(static function (): void {
            throw new LogicException('Organization legal profiles are retained for historical document traceability.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
        ]);
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }
}
